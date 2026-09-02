<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserAccessRevoker
{
    public function revokeWebSession(Request $request, ?string $guard = null): void
    {
        Auth::guard($guard ?? config('fortify.guard'))->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }
    }

    public function revoke(User $user): void
    {
        $user->forceFill([
            'remember_token' => Str::random(60),
            'api_token_expires_at' => now(),
        ])->save();

        DB::table((string) config('session.table', 'sessions'))
            ->where('user_id', $user->id)
            ->delete();
    }
}
