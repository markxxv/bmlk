<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CreatorsController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('createurs', [
            'user' => $user,
            'application' => $user?->roleApplications()->where('role', 'influencer')->latest()->first(),
        ]);
    }

    public function apply(Request $request): RedirectResponse
    {
        // Honeypot: discard unsolicited submissions without creating accounts.
        if ($request->filled('website')) {
            return back()->with('success', 'Merci pour votre intérêt.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'profile' => ['required', 'string', 'min:3', 'max:255'],
            'message' => ['nullable', 'string', 'max:800'],
            'major' => ['accepted'],
            'consent' => ['accepted'],
        ]);

        $email = Str::lower(trim($data['email']));
        $user = $request->user();

        if ($user && Str::lower($user->email) !== $email) {
            throw ValidationException::withMessages([
                'email' => 'Utilisez l’adresse e-mail de votre compte.',
            ]);
        }

        // An existing customer must authenticate; never attach an anonymous
        // request to an account just because the email matches.
        if (! $user && User::query()->whereRaw('LOWER(email) = ?', [$email])->exists()) {
            return redirect()->route('account.login')
                ->with('success', 'Connectez-vous, puis envoyez votre candidature dans Partenariats.');
        }

        $message = 'Profil : '.trim($data['profile']);
        if (filled($data['message'] ?? null)) {
            $message .= "\n\n".trim($data['message']);
        }

        if ($user) {
            if (! $user->hasVerifiedEmail()) {
                throw ValidationException::withMessages([
                    'email' => 'Confirmez votre e-mail avant de candidater.',
                ]);
            }

            if ($user->is_influencer || $user->roleApplications()
                ->where('role', 'influencer')->where('status', 'pending')->exists()) {
                return redirect()->route('account.roles')
                    ->with('success', 'Votre candidature est déjà enregistrée.');
            }

            $user->roleApplications()->create([
                'role' => 'influencer',
                'status' => 'pending',
                'message' => $message,
            ]);

            return redirect()->route('account.roles')
                ->with('success', 'Votre candidature a été envoyée.');
        }

        // New creator: one user + one ordinary influencer application.
        // The account uses the site's existing email verification/password setup.
        $user = DB::transaction(function () use ($data, $email, $message): User {
            $newUser = User::query()->create([
                'name' => trim($data['name']),
                'email' => $email,
            ]);
            $newUser->roleApplications()->create([
                'role' => 'influencer',
                'status' => 'pending',
                'message' => $message,
            ]);

            return $newUser;
        });

        Auth::login($user);
        $request->session()->regenerate();

        try {
            $user->sendEmailVerificationNotification();
        } catch (\Throwable $exception) {
            Log::warning('Creator verification email failed', [
                'user_id' => $user->id,
                'error' => $exception->getMessage(),
            ]);
        }

        return redirect()->route('account.roles')
            ->with('success', 'Candidature enregistrée. Confirmez votre e-mail pour suivre votre demande.');
    }
}
