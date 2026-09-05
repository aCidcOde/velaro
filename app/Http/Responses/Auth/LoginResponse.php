<?php

namespace App\Http\Responses\Auth;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Fortify;

class LoginResponse implements LoginResponseContract
{
    /**
     * @param  Request  $request
     */
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return new JsonResponse(['two_factor' => false]);
        }

        $user = $request->user();

        if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        // Login único com roteamento por perfil (tela 0): o mesmo formulário
        // manda cada um para o seu ambiente. Master -> /backend, revendedor
        // aprovado -> /portal. Quem não é nenhum dos dois cai no destino padrão.
        if ($request->session()->has('url.intended')) {
            return redirect()->intended(Fortify::redirects('login'));
        }

        if (Gate::forUser($user)->allows('access-backend')) {
            return redirect()->route('backend.dashboard');
        }

        if ($user->reseller?->status === 'aprovado') {
            return redirect()->route('portal.dashboard');
        }

        return redirect()->intended(Fortify::redirects('login'));
    }
}
