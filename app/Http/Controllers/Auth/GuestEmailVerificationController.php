<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GuestEmailVerificationController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $guard = Auth::guard(config('fortify.guard'));
        $user = User::query()->findOrFail((int) $request->route('id'));

        if (! hash_equals((string) $user->getKey(), (string) $request->route('id'))) {
            abort(403);
        }

        if (! hash_equals(sha1($user->getEmailForVerification()), (string) $request->route('hash'))) {
            abort(403);
        }

        if ($user->isBlocked()) {
            if ($guard->check()) {
                $guard->logout();
            }

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            return redirect()
                ->route('login')
                ->with('status', User::BLOCKED_MESSAGE);
        }

        if (! $user->hasVerifiedEmail() && $user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        if ($guard->check() && (int) $guard->id() !== $user->id) {
            $guard->logout();

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }
        }

        $guard->login($user, true);

        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        return redirect()->to(route('dashboard', absolute: false).'?verified=1');
    }
}
