<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Throwable;

class MicrosoftEntraController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        if ($redirect = $this->redirectToConfiguredMicrosoftHost($request)) {
            return $redirect;
        }

        return Socialite::driver('microsoft')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        try {
            $microsoftUser = Socialite::driver('microsoft')->user();
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('login')
                ->withErrors(['email' => __('Unable to sign in with Microsoft Entra. Please try again.')]);
        }

        $email = $this->emailFromMicrosoftUser($microsoftUser);

        if ($email === null) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => __('Your Microsoft Entra account did not return an email address.')]);
        }

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if (! $user?->isActive()) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => __('This Microsoft Entra account is not linked to an active portal user.')]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function emailFromMicrosoftUser(SocialiteUser $user): ?string
    {
        $raw = $user->getRaw();

        return collect([
            $raw['mail'] ?? null,
            $user->getEmail(),
            $raw['userPrincipalName'] ?? null,
        ])
            ->filter(fn ($email): bool => is_string($email) && $email !== '')
            ->map(fn (string $email): string => Str::lower($email))
            ->first();
    }

    private function redirectToConfiguredMicrosoftHost(Request $request): ?RedirectResponse
    {
        $callbackUrl = config('services.microsoft.redirect');

        if (! is_string($callbackUrl) || $callbackUrl === '') {
            return null;
        }

        $callbackParts = parse_url($callbackUrl);

        if (! isset($callbackParts['scheme'], $callbackParts['host'])) {
            return null;
        }

        $configuredOrigin = $callbackParts['scheme'].'://'.$callbackParts['host'];

        if (isset($callbackParts['port'])) {
            $configuredOrigin .= ':'.$callbackParts['port'];
        }

        if ($request->getSchemeAndHttpHost() === $configuredOrigin) {
            return null;
        }

        return redirect()->away($configuredOrigin.route('auth.microsoft.redirect', absolute: false));
    }
}
