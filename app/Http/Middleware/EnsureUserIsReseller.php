<?php

namespace App\Http\Middleware;

use App\Models\Reseller;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Portal do Lojista: só entra quem está vinculado a um revendedor APROVADO.
 *
 * O consumidor final não tem login (ele é um customers na carteira de um
 * revendedor), e quem está em pré-cadastro acompanha a solicitação pela rota
 * pública /solicitacao/{protocolo} — não pelo portal. Reprovado e inativo
 * não autenticam para o portal.
 */
class EnsureUserIsReseller
{
    public function handle(Request $request, Closure $next): Response
    {
        $reseller = $request->user()?->reseller;

        if ($reseller === null || $reseller->status !== Reseller::STATUS_APPROVED) {
            abort(403, 'Acesso restrito a revendedores aprovados.');
        }

        return $next($request);
    }
}
