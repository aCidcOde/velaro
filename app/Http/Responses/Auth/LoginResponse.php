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

        // Login único com roteamento por perfil (tela 0): o mesmo formulário manda
        // cada um para o seu ambiente. Master -> /backend, lojista -> /portal.
        if ($request->session()->has('url.intended')) {
            return redirect()->intended(Fortify::redirects('login'));
        }

        if (Gate::forUser($user)->allows('access-backend')) {
            return redirect()->route('backend.dashboard');
        }

        // Um login, um painel. TODO lojista com vínculo vai para `/portal`,
        // aprovado ou não — o painel é que muda de conteúdo conforme o estágio da
        // jornada, e o pré-cadastro é o primeiro passo dela, não um desvio.
        //
        // Antes, quem tinha protocolo era mandado para `/solicitacao/{protocol}`,
        // uma página fora do painel: o lojista terminava o cadastro com um login
        // que o expulsava do próprio produto. A rota pública continua existindo,
        // porque é ela que o link do e-mail e do WhatsApp abre (telas 1.5 e 1.7) e
        // ela funciona sem sessão — deixou apenas de ser destino de quem logou.
        if ($user->reseller !== null) {
            return redirect()->route('portal.dashboard');
        }

        // Sem vinculo com revendedor nao ha ambiente Velaro para rotear: conta interna
        // sem ACL e conta recem-criada em /register caem no destino padrao do Fortify.
        return redirect()->intended(Fortify::redirects('login'));
    }
}
