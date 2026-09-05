<?php

/*
[Modulo: app/Http/Middleware]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Abre uma unica rota do portal — o painel — a qualquer usuario vinculado a um revendedor, aprovado ou nao.
*/

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * O par assimetrico de {@see EnsureUserIsReseller}.
 *
 * O lojista tem UM login e UM painel: o pre-cadastro nao e um estado de excecao
 * fora do sistema, e o primeiro passo da jornada dentro dele. Para isso o painel
 * precisa abrir antes da aprovacao — e so ele.
 *
 * ## Por que a excecao para no painel
 *
 * Afrouxar o `reseller` para aceitar qualquer lojista abriria de uma vez as 18
 * rotas de negocio do portal: catalogo com o **custo B2B** da Velaro, pedidos,
 * clientes finais, financeiro, notas, lotes de pagamento e as regras de preco do
 * revendedor. Quem ainda nao passou pela analise — ou foi reprovado — leria a
 * tabela de custo inteira, que e exatamente o que a aprovacao existe para
 * conceder. Por isso o `reseller` continua estrito, exigindo
 * `status = approved`, e a excecao e nominal: **uma rota**, esta.
 *
 * O painel se vira sozinho com isso porque nao consulta nada de negocio quando o
 * cadastro ainda esta em analise — ele renderiza o acompanhamento da propria
 * solicitacao (etapas, triagem, linha do tempo), que ja e dado do proprio
 * lojista.
 *
 * Ter `reseller_id` nao aprova ninguem: o vinculo nasce no cadastro, junto com a
 * senha escolhida na tela 1.4, e diz apenas *de quem* e a solicitacao.
 */
class EnsureUserBelongsToReseller
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->reseller === null) {
            abort(403, 'Acesso restrito a lojistas cadastrados.');
        }

        return $next($request);
    }
}
