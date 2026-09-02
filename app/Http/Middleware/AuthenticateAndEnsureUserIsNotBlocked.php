<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\UserAccessRevoker;
use Closure;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateAndEnsureUserIsNotBlocked extends Authenticate
{
    public function __construct(AuthFactory $auth, private readonly UserAccessRevoker $revoker)
    {
        parent::__construct($auth);
    }

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle($request, Closure $next, ...$guards): Response
    {
        $this->authenticate($request, $guards);

        /** @var User|null $user */
        $user = $request->user();

        if ($user instanceof User && $user->isBlocked()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => User::BLOCKED_MESSAGE,
                ], 403);
            }

            $guard = isset($guards[0]) && is_string($guards[0]) ? $guards[0] : null;
            $this->revoker->revokeWebSession($request, $guard);

            return redirect()
                ->route('login')
                ->with('status', User::BLOCKED_MESSAGE);
        }

        return $next($request);
    }
}
