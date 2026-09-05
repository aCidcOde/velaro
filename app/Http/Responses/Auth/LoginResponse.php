<?php

namespace App\Http\Responses\Auth;

use App\Models\Reseller;
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

        $reseller = $user->reseller;

        if ($reseller?->status === Reseller::STATUS_APPROVED) {
            return redirect()->route('portal.dashboard');
        }

        // Regra 2 da tela 1.6: pre-cadastro da acesso SOMENTE ao acompanhamento da propria
        // solicitacao. Reprovado e inativo tambem param aqui — veem o motivo, nao o portal.
        if ($reseller !== null && filled($reseller->protocol)) {
            return redirect()->route('site.solicitacao.status', $reseller);
        }

        // Sem vinculo com revendedor nao ha ambiente Velaro para rotear: conta interna
        // sem ACL e conta recem-criada em /register caem no destino padrao do Fortify.
        return redirect()->intended(Fortify::redirects('login'));
    }
}
