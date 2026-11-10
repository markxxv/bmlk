<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class RepairController extends Controller
{
    public function index(): View
    {
        return view('reparation', [
            'metaTitle' => __('Réparation et affûtage d’outils | BLACK MILK France'),
            'metaDescription' => __('Confiez vos ciseaux, pinces et repousse-peaux à notre atelier. Affûtage, diagnostic gratuit avant réparation et formules annuelles BLACK MILK.'),
            'canonical' => route('reparation'),
        ]);
    }

    public function submit(Request $request): RedirectResponse
    {
        // Honeypot: silently ignore automated submissions.
        if ($request->filled('website')) {
            return redirect()->route('reparation')->with('success', __('Votre demande a bien été reçue.'));
        }

        $data = $request->validate([
            'mode' => ['required', 'in:diagnostic,sharpening,subscription'],
            'name' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'consent' => ['accepted'],
        ]);

        $lines = [
            '🛠 Nouvelle demande — Atelier BLACK MILK',
            '',
            'Type : '.match ($data['mode']) {
                'diagnostic' => 'Réparation · diagnostic gratuit',
                'sharpening' => 'Affûtage',
                'subscription' => 'Abonnement annuel',
            },
            'Nom : '.(trim((string) ($data['name'] ?? '')) ?: 'Non renseigné'),
            'E-mail : '.strtolower(trim($data['email'])),
            'Date : '.now()->timezone('Europe/Paris')->format('d/m/Y H:i'),
        ];

        if ($data['mode'] === 'diagnostic') {
            $details = $request->validate([
                'tool' => ['required', 'in:scissors,pliers,pusher,other'],
                'description' => ['required', 'string', 'min:10', 'max:2000'],
                'photos' => ['required', 'array', 'min:1', 'max:3'],
                'photos.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            ]);

            $tool = [
                'scissors' => 'Ciseaux',
                'pliers' => 'Pince à envies',
                'pusher' => 'Repousse-peaux',
                'other' => 'Autre outil',
            ][$details['tool']];

            $lines[] = 'Outil : '.$tool;
            $lines[] = '';
            $lines[] = 'Description :';
            $lines[] = trim($details['description']);
            $lines[] = '';
            $lines[] = 'Photos : '.count($request->file('photos')).' fichier(s) joint(s)';
        } elseif ($data['mode'] === 'sharpening') {
            $details = $request->validate([
                'scissors' => ['required', 'integer', 'min:0', 'max:50'],
                'pliers' => ['required', 'integer', 'min:0', 'max:50'],
                'pushers' => ['required', 'integer', 'min:0', 'max:50'],
            ]);

            $count = $details['scissors'] + $details['pliers'] + $details['pushers'];
            if ($count < 1) {
                throw ValidationException::withMessages([
                    'scissors' => 'Ajoutez au moins un outil pour demander un affûtage.',
                ]);
            }

            $price = $details['scissors'] * 10 + $details['pliers'] * 10 + $details['pushers'] * 9;
            $lines[] = 'Ciseaux : '.$details['scissors'].' × 10 €';
            $lines[] = 'Pinces : '.$details['pliers'].' × 10 €';
            $lines[] = 'Repousse-peaux : '.$details['pushers'].' × 9 €';
            $lines[] = 'Total indicatif : '.$price.' € (hors livraison)';
            $lines[] = 'Livraison offerte : '.($count >= 10 ? 'oui (10 outils ou plus)' : 'non');
        } else {
            $details = $request->validate([
                'plan' => ['required', 'in:start,master,salon'],
            ]);

            $plan = [
                'start' => ['Start', 119, 3],
                'master' => ['Master', 199, 6],
                'salon' => ['Salon', 349, 12],
            ][$details['plan']];

            $lines[] = 'Formule : '.$plan[0];
            $lines[] = 'Prix affiché : '.$plan[1].' € / an';
            $lines[] = 'Capacité : jusqu’à '.$plan[2].' outils × 4 affûtages';
            $lines[] = 'Statut : demande d’information, sans paiement';
        }

        $token = trim((string) config('services.telegram.bot_token'));
        $chatId = trim((string) config('services.telegram.chat_id'));

        if ($token === '' || $chatId === '') {
            Log::error('Repair Telegram request: bot configuration is missing.');

            return back()->withInput()->withErrors([
                'request' => __('Le service est momentanément indisponible. Veuillez réessayer plus tard.'),
            ]);
        }

        try {
            // Direct HTTP requests only: no job, database queue or local photo storage.
            $message = Http::acceptJson()->timeout(15)
                ->post('https://api.telegram.org/bot'.$token.'/sendMessage', [
                    'chat_id' => $chatId,
                    'text' => implode("\n", $lines),
                    'link_preview_options' => ['is_disabled' => true],
                ]);

            $this->ensureSent($message->successful(), $message->json('ok'), $message->status());

            if ($data['mode'] === 'diagnostic') {
                foreach ($request->file('photos', []) as $index => $photo) {
                    // sendDocument preserves the original resolution for the artisan.
                    $photoResponse = Http::acceptJson()->timeout(20)
                        ->attach(
                            'document',
                            file_get_contents($photo->getRealPath()),
                            $photo->getClientOriginalName(),
                            ['Content-Type' => $photo->getMimeType()]
                        )
                        ->post('https://api.telegram.org/bot'.$token.'/sendDocument', array_filter([
                            'chat_id' => $chatId,
                            'caption' => '🛠 Photo '.($index + 1).' — '.strtolower(trim($data['email'])),
                            'reply_to_message_id' => $message->json('result.message_id'),
                        ], static fn ($value) => $value !== null));

                    $this->ensureSent($photoResponse->successful(), $photoResponse->json('ok'), $photoResponse->status());
                }
            }
        } catch (Throwable $exception) {
            Log::error('Repair Telegram request failed.', [
                'mode' => $data['mode'],
                'error' => str_replace($token, '[REDACTED]', $exception->getMessage()),
            ]);

            return back()->withInput()->withErrors([
                'request' => __('L’envoi à notre atelier a échoué ou est incomplet. Contactez-nous par e-mail avant de réessayer pour éviter un doublon.'),
            ]);
        }

        return redirect()->route('reparation')->with(
            'success',
            $data['mode'] === 'diagnostic'
                ? __('Votre demande de diagnostic et vos photos ont été envoyées à notre atelier.')
                : __('Votre demande a été envoyée à notre atelier. Nous vous contacterons par e-mail.')
        );
    }

    private function ensureSent(bool $successful, mixed $ok, int $status): void
    {
        if (! $successful || $ok !== true) {
            throw new RuntimeException('Telegram API request failed (HTTP '.$status.').');
        }
    }
}
