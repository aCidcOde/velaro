<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\UserAccessRevoker;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsNotBlocked
{
    public function __construct(private readonly UserAccessRevoker $revoker) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        if (! $user instanceof User || ! $user->isBlocked()) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => User::BLOCKED_MESSAGE,
            ], 403);
        }

        $this->revoker->revokeWebSession($request);

        return redirect()
            ->route('login')
            ->with('status', User::BLOCKED_MESSAGE);
    }
}
