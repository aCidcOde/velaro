<?php

/*
[Modulo: app/Services/Backend]
@Author: André Gomes ( @acidcode )
@since 2026-09-06
Base de revendedores do Painel Master: listagem, indicadores, cadastro manual e aprovacao na propria tela.
*/

namespace App\Services\Backend;

use App\Models\Reseller;
use App\Models\ResellerCnae;
use App\Models\ResellerStatusEvent;
use App\Models\User;
use App\Services\AdminAuditLogger;
use App\Services\Site\ResellerRegistrationService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Tela 3.10. Duas coisas na mesma tela (regra 1): a gestao dos lojistas ja
 * habilitados e o cadastro manual — o caminho de quem a Velaro prospecta, que
 * nao passa pela fila da 3.11 e pode ser aprovado aqui mesmo (regra 2).
 *
 * O numero de protocolo sai do {@see ResellerRegistrationService}, e nao de uma
 * segunda implementacao: dois geradores de sequencia sobre a mesma coluna
 * colidiriam no primeiro cadastro simultaneo.
 */
class RevendedorService
{
    public function __construct(
        private readonly AdminAuditLogger $auditoria,
        private readonly ResellerRegistrationService $cadastroPublico,
    ) {}

    /**
     * @param  array{status?: string|null, busca?: string|null}  $filtros
     * @return LengthAwarePaginator<int, Reseller>
     */
    public function listar(array $filtros = []): LengthAwarePaginator
    {
        $status = $filtros['status'] ?? null;
        $busca = trim((string) ($filtros['busca'] ?? ''));

        return Reseller::query()
            ->with(['cnaes', 'verifications' => fn ($q) => $q->latest('checked_at')])
            ->when(
                is_string($status) && $status !== '',
                fn (Builder $q): Builder => $q->where('status', $status),
            )
            ->when($busca !== '', function (Builder $q) use ($busca): Builder {
                $termo = '%'.$busca.'%';

                return $q->where(function (Builder $inner) use ($termo): void {
                    $inner->where('trade_name', 'like', $termo)
                        ->orWhere('legal_name', 'like', $termo)
                        ->orWhere('contact_name', 'like', $termo)
                        ->orWhere('cnpj', 'like', $termo)
                        ->orWhere('code', 'like', $termo);
                });
            })
            ->latest('created_at')
            ->paginate(5)
            ->withQueryString();
    }

    /**
     * @return list<array{rotulo: string, valor: int, icone: string, tom: string, filtro: array<string, scalar>}>
     */
    public function kpis(): array
    {
        $inicioDoMes = now()->startOfMonth();

        return [
            [
                'rotulo' => 'Revendedores ativos',
                'valor' => Reseller::query()->where('status', Reseller::STATUS_APPROVED)->count(),
                'icone' => 'store',
                'tom' => 'ok',
                'filtro' => ['status' => Reseller::STATUS_APPROVED],
            ],
            [
                'rotulo' => 'Pendentes de aprovação',
                'valor' => Reseller::query()->whereIn('status', [Reseller::STATUS_PENDING, Reseller::STATUS_AWAITING_INFO])->count(),
                'icone' => 'clock',
                'tom' => 'warn',
                'filtro' => ['status' => Reseller::STATUS_PENDING],
            ],
            [
                'rotulo' => 'Cadastros manuais no mês',
                'valor' => Reseller::query()
                    ->where('registration_type', Reseller::REGISTRATION_TYPE_MANUAL)
                    ->where('created_at', '>=', $inicioDoMes)->count(),
                'icone' => 'user-plus',
                'tom' => 'brand',
                'filtro' => [],
            ],
            [
                'rotulo' => 'CNAEs verificados',
                'valor' => ResellerCnae::query()->whereNotNull('compatible')->count(),
                'icone' => 'check',
                'tom' => 'info',
                'filtro' => [],
            ],
        ];
    }

    /**
     * Cadastro manual. Nasce em `pending` como qualquer outro: quem aprova e a
     * acao seguinte, com log — nao o ato de cadastrar.
     *
     * @param  array<string, mixed>  $dados
     * @param  list<array{code: string, description?: string|null, compatible?: bool|null}>  $cnaes
     */
    public function cadastrarManualmente(array $dados, array $cnaes, User $ator): Reseller
    {
        return DB::transaction(function () use ($dados, $cnaes, $ator): Reseller {
            $reseller = Reseller::create([
                'protocol' => $this->cadastroPublico->nextProtocol(),
                'legal_name' => $dados['legal_name'],
                'trade_name' => $dados['trade_name'],
                'cnpj' => $dados['cnpj'],
                'state_registration' => $dados['state_registration'] ?? null,
                'contact_name' => $dados['contact_name'],
                'contact_cpf' => $dados['contact_cpf'] ?? null,
                'email' => $dados['email'],
                'phone' => $dados['phone'],
                'whatsapp' => $dados['whatsapp'] ?? null,
                'postal_code' => $dados['postal_code'] ?? null,
                'street' => $dados['street'] ?? null,
                'street_number' => $dados['street_number'] ?? null,
                'address_complement' => $dados['address_complement'] ?? null,
                'district' => $dados['district'] ?? null,
                'city' => $dados['city'] ?? null,
                'state' => $dados['state'] ?? null,
                'internal_notes' => $dados['internal_notes'] ?? null,
                'registration_type' => Reseller::REGISTRATION_TYPE_MANUAL,
                'status' => Reseller::STATUS_PENDING,
            ]);

            foreach ($cnaes as $cnae) {
                ResellerCnae::updateOrCreate(
                    ['reseller_id' => $reseller->id, 'code' => $cnae['code']],
                    [
                        'description' => $cnae['description'] ?? null,
                        'compatible' => $cnae['compatible'] ?? null,
                        'is_primary' => false,
                    ],
                );
            }

            ResellerStatusEvent::create([
                'reseller_id' => $reseller->id,
                'from_status' => null,
                'to_status' => Reseller::STATUS_PENDING,
                'actor_id' => $ator->id,
                'note' => 'Cadastro manual criado pelo Painel Interno.',
            ]);

            $this->auditoria->log('velaro.reseller.created', $reseller, null, [
                'registration_type' => Reseller::REGISTRATION_TYPE_MANUAL,
                'status' => Reseller::STATUS_PENDING,
            ]);

            return $reseller->refresh();
        });
    }

    /**
     * Aprova o lojista direto na 3.10. Acao sensivel: §7.
     */
    public function aprovar(Reseller $reseller, User $ator, string $justificativa): Reseller
    {
        return DB::transaction(function () use ($reseller, $ator, $justificativa): Reseller {
            $origem = $reseller->status;

            ResellerStatusEvent::create([
                'reseller_id' => $reseller->id,
                'from_status' => $origem,
                'to_status' => Reseller::STATUS_APPROVED,
                'actor_id' => $ator->id,
                'note' => $justificativa,
            ]);

            $reseller->forceFill([
                'status' => Reseller::STATUS_APPROVED,
                'approved_at' => now(),
                'approved_by' => $ator->id,
                'rejected_at' => null,
                'rejection_reason' => null,
            ])->save();

            $this->auditoria->log('velaro.reseller.approved', $reseller, ['status' => $origem], [
                'status' => Reseller::STATUS_APPROVED,
                'note' => $justificativa,
            ]);

            return $reseller->refresh();
        });
    }
}
