<?php

/*
[App/Http/Middleware]
@Author: André Gomes ( @acidcode )
@since 2026-02-09
Autentica requisicoes mobile via token bearer persistido no usuario.
*/

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateMobileToken
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $plainToken = $request->bearerToken();

        if (! is_string($plainToken) || $plainToken === '') {
            return response()->json([
                'message' => 'Token de acesso nao informado.',
            ], 401);
        }

        $tokenHash = hash('sha256', $plainToken);

        $user = User::query()
            ->where('api_token_hash', $tokenHash)
            ->first();

        if (! $user) {
            return response()->json([
                'message' => 'Token invalido ou expirado.',
            ], 401);
        }

        $request->setUserResolver(fn (): User => $user);

        if ($user->isBlocked()) {
            return response()->json([
                'message' => User::BLOCKED_MESSAGE,
            ], 403);
        }

        $tokenExpiresAt = $user->api_token_expires_at;

        if ($tokenExpiresAt instanceof Carbon && $tokenExpiresAt->lte(now())) {
            return response()->json([
                'message' => 'Token invalido ou expirado.',
            ], 401);
        }

        return $next($request);
    }
}
