<?php

namespace App\Services;

use App\Models\RoleApplication;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class PartnerApplicationTelegramNotifier
{
    /**
     * Sends directly (never through the queue). Only the separate reminder
     * job invokes this with $reminder = true.
     *
     * @throws Throwable
     */
    public function send(int $applicationId, bool $reminder = false): bool
    {
        $token = trim((string) config('services.telegram.bot_token'));
        $chatId = trim((string) config('services.telegram.chat_id'));

        if ($token === '' || $chatId === '') {
            throw new RuntimeException('Telegram bot token or chat ID is missing.');
        }

        $claim = now();
        $query = RoleApplication::query()
            ->whereKey($applicationId)
            ->where('status', 'pending')
            ->where(function ($query): void {
                $query->whereNull('telegram_claimed_at')
                    ->orWhere('telegram_claimed_at', '<=', now()->subMinutes(5));
            });

        if ($reminder) {
            $query->whereNotNull('telegram_first_sent_at')
                ->where('telegram_last_sent_at', '<=', now()->subDay());
        } else {
            $query->whereNull('telegram_first_sent_at');
        }

        if ($query->update(['telegram_claimed_at' => $claim]) !== 1) {
            return false;
        }

        try {
            $application = RoleApplication::query()->with('user')->findOrFail($applicationId);

            foreach ($this->messages($application, $reminder) as $text) {
                $response = Http::acceptJson()->timeout(12)
                    ->post('https://api.telegram.org/bot'.$token.'/sendMessage', [
                        'chat_id' => $chatId,
                        'text' => $text,
                        'parse_mode' => 'HTML',
                        'link_preview_options' => ['is_disabled' => true],
                    ]);

                if (! $response->successful() || $response->json('ok') !== true) {
                    throw new RuntimeException(
                        'Telegram HTTP '.$response->status().': '.(string) $response->json('description', 'sendMessage failed')
                    );
                }
            }

            $updates = [
                'telegram_last_sent_at' => $claim,
                'telegram_claimed_at' => null,
                'telegram_reminder_queued_at' => null,
            ];

            if (! $reminder) {
                $updates['telegram_first_sent_at'] = $claim;
            }

            RoleApplication::query()->whereKey($applicationId)
                ->where('telegram_claimed_at', $claim)
                ->update($updates);

            return true;
        } catch (Throwable $exception) {
            RoleApplication::query()->whereKey($applicationId)
                ->where('telegram_claimed_at', $claim)
                ->update(['telegram_claimed_at' => null]);

            Log::error('Partner application Telegram notification failed.', [
                'application_id' => $applicationId,
                'reminder' => $reminder,
                'error' => str_replace($token, '[REDACTED]', $exception->getMessage()),
            ]);

            throw $exception;
        }
    }

    /** @return list<string> */
    private function messages(RoleApplication $application, bool $reminder): array
    {
        $role = match ($application->role) {
            'wholesale' => 'Revendeur / Grossiste',
            'influencer' => 'Créatrice / Influenceuse',
            default => (string) $application->role,
        };

        $lines = [
            $reminder
                ? '⏰ <b>RAPPEL — Demande de partenariat en attente</b>'
                : '🤝 <b>Nouvelle demande de partenariat</b>',
            '',
            'Programme : <b>'.e($role).'</b>',
            'Statut : En attente de validation',
            'Reçue le : '.e($application->created_at?->timezone('Europe/Paris')->format('d/m/Y H:i') ?? '—'),
            '',
            '👤 <b>Candidat</b>',
        ];

        $this->appendField($lines, 'Nom : ', $application->user?->name);
        $this->appendField($lines, 'E-mail : ', $application->user?->email);

        if ($reminder) {
            $lines[] = '';
            $lines[] = '⚠️ Cette demande attend toujours une décision. Merci de la traiter.';
        }

        if (filled($application->message)) {
            $lines[] = '';
            $lines[] = '📝 <b>Message du candidat</b>';
            $this->appendField($lines, '', $application->message);
        }

        $lines[] = '';
        $lines[] = '<a href="'.e(url('/admin/role-applications/'.$application->id.'/edit')).'">Examiner la demande sur le site</a>';

        $messages = [];
        $current = '';

        foreach ($lines as $line) {
            $next = $current === '' ? $line : $current."\n".$line;

            if ($current !== '' && mb_strlen($next, 'UTF-8') > 3500) {
                $messages[] = $current;
                $current = '<b>Demande #'.$application->id.' — suite</b>'."\n".$line;
            } else {
                $current = $next;
            }
        }

        if ($current !== '') {
            $messages[] = $current;
        }

        return $messages;
    }

    /** @param list<string> $lines */
    private function appendField(array &$lines, string $label, mixed $value): void
    {
        $value = trim((string) ($value ?? ''));

        if ($value === '') {
            return;
        }

        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        // Chunk before HTML escaping to stay below Telegram's message limit,
        // including when applicants enter lots of angle brackets or ampersands.
        foreach (mb_str_split($value, 200, 'UTF-8') as $index => $part) {
            $lines[] = ($index === 0 ? $label : '  ').e($part);
        }
    }
}
