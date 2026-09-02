<?php

/*
[Modulo: app/Http/Controllers/Auth]
@Author: André Gomes ( @acidcode )
@since 2026-02-22
Controla o login social com Google (redirect para OAuth e callback de autenticacao).
*/

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\WelcomeMail;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')
            ->scopes(['openid', 'profile', 'email'])
            ->redirect();
    }

    public function callback(): RedirectResponse
    {
        try {
            /** @var SocialiteUser $googleUser */
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('login')
                ->with('status', 'Não foi possível autenticar com o Google. Tente novamente.');
        }

        $email = trim((string) $googleUser->getEmail());

        if ($email === '') {
            return redirect()
                ->route('login')
                ->with('status', 'Não foi possível obter o e-mail da conta Google.');
        }

        $name = trim((string) ($googleUser->getName()
            ?: $googleUser->getNickname()
            ?: Str::before($email, '@')));

        $googleId = (string) $googleUser->getId();

        $user = User::query()
            ->where('google_id', $googleId)
            ->first();

        if ($user !== null && $user->isBlocked()) {
            return redirect()
                ->route('login')
                ->with('status', User::BLOCKED_MESSAGE);
        }

        if ($user === null) {
            $existingUserWithEmail = User::query()
                ->where('email', $email)
                ->first();

            if ($existingUserWithEmail !== null) {
                if ($existingUserWithEmail->isBlocked()) {
                    return redirect()
                        ->route('login')
                        ->with('status', User::BLOCKED_MESSAGE);
                }

                return redirect()
                    ->route('login')
                    ->with('status', 'Ja existe uma conta com este e-mail. Entre com sua senha para vincular o Google manualmente.');
            }
        }

        $isNewUser = false;

        if ($user === null) {
            $isNewUser = true;

            $user = User::query()->create([
                'name' => $name !== '' ? $name : 'Usuário Google',
                'email' => $email,
                'phone' => null,
                'document' => null,
                'password' => Str::random(40),
                'google_id' => $googleId,
            ]);

            $user->forceFill([
                'email_verified_at' => now(),
            ])->save();
        } else {
            $user->forceFill([
                'name' => $name !== '' ? $name : $user->name,
                'email' => $email,
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();
        }

        if ($isNewUser) {
            Mail::to($user)->send(new WelcomeMail($user));
        }

        if ($user->isBlocked()) {
            return redirect()
                ->route('login')
                ->with('status', User::BLOCKED_MESSAGE);
        }

        Auth::login($user, true);
        request()->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
