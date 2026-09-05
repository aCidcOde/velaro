<?php

/*
[Modulo: app/Http/Middleware]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Restringe o acompanhamento do cadastro a quem enviou a solicitacao, ao dono do login ou ao Master.
*/

namespace App\Http\Middleware;

use App\Models\Reseller;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

/**
 * As telas 1.5 e 1.6 imprimem razao social, CNPJ, responsavel, e-mail e WhatsApp
 * do solicitante, e o protocolo e sequencial por ano (VEL-2026-0148). Sem este
 * gate qualquer visitante percorre VEL-AAAA-0001..9999 e colhe o cadastro
 * inteiro da base — por isso a regra 2 da tela 1.6 diz que o pre-cadastro ve
 * *somente* a propria solicitacao.
 *
 * Tres formas legitimas de chegar: a sessao que acabou de enviar o formulario,
 * o login vinculado a solicitacao e o Perfil Master. A tela 1.7 fica de fora
 * porque nao exibe dado pessoal nenhum e o doc a classifica como link
 * transacional.
 */
class EnsureCanTrackReseller
{
    /**
     * Protocolos que ESTA sessao criou — o navegador que enviou o formulario.
     */
    public const SESSION_KEY = 'site.solicitacoes';

    /**
     * Marca o protocolo como pertencente a sessao atual, logo apos o envio.
     */
    public static function remember(Request $request, Reseller $reseller): void
    {
        $stored = $request->session()->get(self::SESSION_KEY, []);
        $protocols = is_array($stored) ? $stored : [];
        $protocols[] = $reseller->protocol;

        $request->session()->put(self::SESSION_KEY, array_values(array_unique($protocols)));
    }

    public function handle(Request $request, Closure $next): Response
    {
        $reseller = $request->route('reseller');

        if (! $reseller instanceof Reseller || $this->allows($request, $reseller)) {
            return $next($request);
        }

        if ($request->user() === null) {
            return redirect()->guest(route('login'))
                ->with('status', 'Faça login para acompanhar sua solicitação.');
        }

        abort(403, 'Esta solicitação pertence a outro cadastro.');
    }

    private function allows(Request $request, Reseller $reseller): bool
    {
        $stored = $request->session()->get(self::SESSION_KEY, []);

        if (is_array($stored) && in_array($reseller->protocol, $stored, true)) {
            return true;
        }

        $user = $request->user();

        if ($user === null) {
            return false;
        }

        // O vinculo `users.reseller_id` nasce junto com o cadastro, entao ele
        // sozinho ja identifica o dono da solicitacao.
        //
        // Aqui tambem havia uma comparacao por igualdade de e-mail, de quando o
        // usuario nascia sem vinculo. Ela era fragil dos dois lados: bastava o
        // lojista trocar o e-mail do login, ou a equipe corrigir o e-mail da
        // solicitacao, para ele perder o acesso ao proprio cadastro — e, no
        // sentido contrario, qualquer conta que viesse a usar aquele endereco
        // herdava a solicitacao inteira (razao social, CNPJ, CPF do responsavel,
        // WhatsApp). Chave de identidade e a FK, nao um campo editavel.
        return $user->reseller_id === $reseller->id
            || Gate::forUser($user)->allows('access-backend');
    }
}
