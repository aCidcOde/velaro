<?php

namespace App\Http\Middleware;

use App\Models\Reseller;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * As 18 rotas de negócio do Portal do Lojista: só entra quem está vinculado a um
 * revendedor APROVADO.
 *
 * O consumidor final não tem login (ele é um customers na carteira de um
 * revendedor). Quem está em pré-cadastro, foi reprovado ou está inativo entra no
 * portal — mas só no painel, pela rota que usa
 * {@see EnsureUserBelongsToReseller}, e lá vê o estágio da própria jornada.
 * Daqui para dentro é a operação já habilitada: catálogo com custo B2B, pedidos,
 * clientes finais, financeiro e regras de preço. Este middleware continua
 * estrito de propósito — a exceção do painel é nominal e vale só para ele.
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
