<?php

/*
[Modulo: app/Services/Backend]
@Author: André Gomes ( @acidcode )
@since 2026-09-06
Fila de pre-cadastros do Painel Master e as tres decisoes humanas sobre uma solicitacao de lojista.
*/

namespace App\Services\Backend;

use App\Models\Reseller;
use App\Models\ResellerStatusEvent;
use App\Models\User;
use App\Services\AdminAuditLogger;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Tela 3.11. A triagem automatica (`reseller_verifications`) e pre-aprovacao;
 * a decisao e humana e fica registrada com justificativa (regra 2, Anexo I §3.7).
 *
 * As tres acoes compartilham a mesma forma: transicao de status, evento em
 * `reseller_status_events` com o ator e a justificativa, e entrada em
 * `audit_logs` — as tres escritas numa transacao so. Uma solicitacao que mudasse
 * de status sem o evento perderia a justificativa que o §7 exige, e e por ela que
 * o lojista descobre o que precisa reenviar.
 */
class PreCadastroService
{
    /** Status que a fila da 3.11 atende. Aprovado e reprovado saem para a tela 3.10. */
    public const STATUS_EM_FILA = [
        Reseller::STATUS_PENDING,
        Reseller::STATUS_AWAITING_INFO,
    ];

    public function __construct(private readonly AdminAuditLogger $auditoria) {}

    /**
     * @param  array{status?: string|null, busca?: string|null, periodo?: int|string|null}  $filtros
     * @return LengthAwarePaginator<int, Reseller>
     */
    public function fila(array $filtros = []): LengthAwarePaginator
    {
        $status = $filtros['status'] ?? null;
        $busca = trim((string) ($filtros['busca'] ?? ''));
        $periodo = $filtros['periodo'] ?? 30;

        return Reseller::query()
            // `verifications` e HasMany e nao ha relacao "ultima" no model — e
            // app/Models esta congelado no handoff. A tela usa a mais recente,
            // entao o eager load ja vem ordenado por checked_at.
            ->with(['cnaes', 'documents', 'verifications' => fn ($q) => $q->latest('checked_at')])
            ->when(
                is_string($status) && in_array($status, self::STATUS_EM_FILA, true),
                fn (Builder $q): Builder => $q->where('status', $status),
                fn (Builder $q): Builder => $q->whereIn('status', self::STATUS_EM_FILA),
            )
            // `periodo` = 0 significa "todo o historico": e o que os cartoes de KPI
            // usam, para o numero do cartao e a lista que ele abre nao discordarem.
            ->when(
                is_numeric($periodo) && (int) $periodo > 0,
                fn (Builder $q): Builder => $q->where('created_at', '>=', now()->subDays((int) $periodo)),
            )
            ->when($busca !== '', function (Builder $q) use ($busca): Builder {
                $termo = '%'.$busca.'%';

                return $q->where(function (Builder $inner) use ($termo): void {
                    $inner->where('trade_name', 'like', $termo)
                        ->orWhere('legal_name', 'like', $termo)
                        ->orWhere('contact_name', 'like', $termo)
                        ->orWhere('cnpj', 'like', $termo)
                        ->orWhere('protocol', 'like', $termo);
                });
            })
            ->latest('created_at')
            ->paginate(8)
            ->withQueryString();
    }

    /**
     * Os 5 KPIs do topo da tela.
     *
     * @return list<array{rotulo: string, valor: int, icone: string, tom: string, filtro: array<string, scalar>}>
     */
    public function kpis(): array
    {
        $inicioDoMes = now()->startOfMonth();

        return [
            [
                'rotulo' => 'Solicitações recebidas',
                'valor' => Reseller::query()->whereIn('status', self::STATUS_EM_FILA)->count(),
                'icone' => 'user-plus',
                'tom' => 'brand',
                'filtro' => ['periodo' => 0],
            ],
            [
                'rotulo' => 'Aguardando decisão',
                'valor' => Reseller::query()->where('status', Reseller::STATUS_PENDING)->count(),
                'icone' => 'clock',
                'tom' => 'warn',
                'filtro' => ['status' => Reseller::STATUS_PENDING, 'periodo' => 0],
            ],
            [
                'rotulo' => 'Aguardando informações',
                'valor' => Reseller::query()->where('status', Reseller::STATUS_AWAITING_INFO)->count(),
                'icone' => 'info',
                'tom' => 'info',
                'filtro' => ['status' => Reseller::STATUS_AWAITING_INFO, 'periodo' => 0],
            ],
            [
                'rotulo' => 'Aprovadas no mês',
                'valor' => Reseller::query()->where('status', Reseller::STATUS_APPROVED)
                    ->where('approved_at', '>=', $inicioDoMes)->count(),
                'icone' => 'check',
                'tom' => 'ok',
                'filtro' => [],
            ],
            [
                'rotulo' => 'Reprovadas no mês',
                'valor' => Reseller::query()->where('status', Reseller::STATUS_REJECTED)
                    ->where('rejected_at', '>=', $inicioDoMes)->count(),
                'icone' => 'x',
                'tom' => 'danger',
                'filtro' => [],
            ],
        ];
    }

    /**
     * Aprova e libera o acesso de Parceiro Premium. Acao sensivel: §7.
     */
    public function aprovar(Reseller $reseller, User $ator, string $justificativa): Reseller
    {
        return $this->decidir(
            $reseller,
            $ator,
            Reseller::STATUS_APPROVED,
            $justificativa,
            'velaro.prospect.approved',
            fn (Reseller $r): array => [
                'status' => Reseller::STATUS_APPROVED,
                'approved_at' => now(),
                'approved_by' => $ator->id,
                // Uma aprovacao apaga a reprovacao anterior: o cadastro que volta a
                // ser analisado e aprovado nao pode continuar exibindo o motivo da
                // recusa antiga na 3.10 e na 1.6.
                'rejected_at' => null,
                'rejection_reason' => null,
            ],
        );
    }

    /**
     * Reprova com justificativa. Acao sensivel: §7.
     */
    public function reprovar(Reseller $reseller, User $ator, string $justificativa): Reseller
    {
        return $this->decidir(
            $reseller,
            $ator,
            Reseller::STATUS_REJECTED,
            $justificativa,
            'velaro.prospect.rejected',
            fn (Reseller $r): array => [
                'status' => Reseller::STATUS_REJECTED,
                'rejected_at' => now(),
                'rejection_reason' => $justificativa,
            ],
        );
    }

    /**
     * Devolve a solicitacao ao lojista pedindo documento adicional.
     *
     * E a contraparte do reenvio da tela 1.6: em `awaiting_info` o lojista reabre
     * o envio de documentos, e o envio devolve o cadastro para `pending`.
     */
    public function solicitarInformacoes(Reseller $reseller, User $ator, string $justificativa): Reseller
    {
        return $this->decidir(
            $reseller,
            $ator,
            Reseller::STATUS_AWAITING_INFO,
            $justificativa,
            'velaro.prospect.info_requested',
            fn (Reseller $r): array => ['status' => Reseller::STATUS_AWAITING_INFO],
        );
    }

    /**
     * @param  callable(Reseller): array<string, mixed>  $atributos
     */
    private function decidir(
        Reseller $reseller,
        User $ator,
        string $destino,
        string $justificativa,
        string $acaoDeAuditoria,
        callable $atributos,
    ): Reseller {
        return DB::transaction(function () use ($reseller, $ator, $destino, $justificativa, $acaoDeAuditoria, $atributos): Reseller {
            $origem = $reseller->status;
            $antes = ['status' => $origem];

            ResellerStatusEvent::create([
                'reseller_id' => $reseller->id,
                'from_status' => $origem,
                'to_status' => $destino,
                'actor_id' => $ator->id,
                'note' => $justificativa,
            ]);

            $reseller->forceFill($atributos($reseller))->save();

            $this->auditoria->log($acaoDeAuditoria, $reseller, $antes, [
                'status' => $destino,
                'note' => $justificativa,
            ]);

            return $reseller->refresh();
        });
    }
}
