<?php

/*
[Modulo: tests/Feature/Backend]
@Author: André Gomes ( @acidcode )
@since 2026-09-06
Cobre a tela 3.11: fila, permissoes granulares e as tres decisoes com log e evento de status.
*/

namespace Tests\Feature\Backend;

use App\Models\AclPermission;
use App\Models\AuditLog;
use App\Models\Reseller;
use App\Models\ResellerStatusEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PreCadastroTest extends TestCase
{
    use RefreshDatabase;

    /**
     * As tres decisoes dependem de rotas POST que ainda nao existem em
     * routes/velaro.php — arquivo de outro territorio. O teste pula enquanto a
     * rota faltar e passa a valer sozinho assim que ela entrar, em vez de deixar
     * a suite vermelha ou de fingir que a acao esta coberta.
     */
    private function exigirRota(string $nome): void
    {
        if (! Route::has($nome)) {
            $this->markTestSkipped("Rota [{$nome}] ainda nao registrada (ver patch da tela 3.11).");
        }
    }

    private function solicitacao(string $status = Reseller::STATUS_PENDING): Reseller
    {
        return Reseller::factory()->create(['status' => $status]);
    }

    public function test_a_fila_lista_apenas_solicitacoes_pendentes_ou_aguardando_informacoes(): void
    {
        $admin = $this->createBackendAdmin();

        $naFila = $this->solicitacao();
        $devolvida = $this->solicitacao(Reseller::STATUS_AWAITING_INFO);
        $aprovada = $this->solicitacao(Reseller::STATUS_APPROVED);

        $this->actingAs($admin)
            ->get(route('backend.pre-cadastros.index', ['periodo' => 0]))
            ->assertOk()
            ->assertSee($naFila->trade_name)
            ->assertSee($devolvida->trade_name)
            ->assertDontSee($aprovada->trade_name);
    }

    public function test_usuario_sem_a_permissao_nao_ve_a_fila(): void
    {
        // Admin com acesso ao backend, mas sem `velaro.prospects.view`: o gate
        // granular precisa barrar mesmo quem ja passou pelo `access-backend`.
        $admin = $this->createBackendAdmin();
        // A coluna do override e `is_allowed`; negar aqui prova que o gate granular
        // barra mesmo quem ja passou pelo `access-backend`.
        $admin->permissionOverrides()->updateOrCreate(
            ['permission_id' => $this->permissaoId('velaro.prospects.view')],
            ['is_allowed' => false],
        );

        $this->actingAs($admin->refresh())
            ->get(route('backend.pre-cadastros.index'))
            ->assertForbidden();
    }

    public function test_aprovar_muda_o_status_registra_evento_e_gera_log(): void
    {
        $this->exigirRota('backend.pre-cadastros.aprovar');

        $admin = $this->createBackendAdmin();
        $reseller = $this->solicitacao();

        $this->actingAs($admin)
            ->post(route('backend.pre-cadastros.aprovar', $reseller), [
                'justificativa' => 'CNPJ ativo e CNAEs compatíveis com revenda de joias.',
            ])
            ->assertRedirect(route('backend.pre-cadastros.show', $reseller));

        $reseller->refresh();
        $this->assertSame(Reseller::STATUS_APPROVED, $reseller->status);
        $this->assertNotNull($reseller->approved_at);
        $this->assertSame($admin->id, $reseller->approved_by);

        $this->assertDatabaseHas('reseller_status_events', [
            'reseller_id' => $reseller->id,
            'from_status' => Reseller::STATUS_PENDING,
            'to_status' => Reseller::STATUS_APPROVED,
            'actor_id' => $admin->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'velaro.prospect.approved',
            'target_id' => $reseller->id,
        ]);
    }

    public function test_reprovar_guarda_a_justificativa_no_cadastro(): void
    {
        $this->exigirRota('backend.pre-cadastros.reprovar');

        $admin = $this->createBackendAdmin();
        $reseller = $this->solicitacao();

        $this->actingAs($admin)
            ->post(route('backend.pre-cadastros.reprovar', $reseller), [
                'justificativa' => 'CNAE principal incompatível com revenda de joias.',
            ])
            ->assertRedirect(route('backend.pre-cadastros.show', $reseller));

        $reseller->refresh();
        $this->assertSame(Reseller::STATUS_REJECTED, $reseller->status);
        $this->assertNotNull($reseller->rejected_at);
        $this->assertStringContainsString('CNAE principal', (string) $reseller->rejection_reason);
    }

    public function test_solicitar_informacoes_devolve_para_o_lojista(): void
    {
        $this->exigirRota('backend.pre-cadastros.solicitar-informacoes');

        $admin = $this->createBackendAdmin();
        $reseller = $this->solicitacao();

        $this->actingAs($admin)
            ->post(route('backend.pre-cadastros.solicitar-informacoes', $reseller), [
                'justificativa' => 'Reenvie o cartão CNPJ legível e o contrato social completo.',
            ])
            ->assertRedirect(route('backend.pre-cadastros.show', $reseller));

        // É este estado que abre o reenvio de documentos na tela 1.6.
        $this->assertSame(Reseller::STATUS_AWAITING_INFO, $reseller->refresh()->status);
        $this->assertDatabaseHas('reseller_status_events', [
            'reseller_id' => $reseller->id,
            'to_status' => Reseller::STATUS_AWAITING_INFO,
        ]);
    }

    public function test_a_justificativa_e_obrigatoria_em_acao_sensivel(): void
    {
        $this->exigirRota('backend.pre-cadastros.aprovar');

        $admin = $this->createBackendAdmin();
        $reseller = $this->solicitacao();

        $this->actingAs($admin)
            ->from(route('backend.pre-cadastros.show', $reseller))
            ->post(route('backend.pre-cadastros.aprovar', $reseller), ['justificativa' => ''])
            ->assertSessionHasErrors('justificativa');

        $this->assertSame(Reseller::STATUS_PENDING, $reseller->refresh()->status);
        $this->assertSame(0, ResellerStatusEvent::query()->where('reseller_id', $reseller->id)->count());
        $this->assertSame(0, AuditLog::query()->where('action', 'velaro.prospect.approved')->count());
    }

    public function test_nao_se_decide_duas_vezes_a_mesma_solicitacao(): void
    {
        $this->exigirRota('backend.pre-cadastros.reprovar');

        $admin = $this->createBackendAdmin();
        $reseller = $this->solicitacao(Reseller::STATUS_APPROVED);

        $this->actingAs($admin)
            ->post(route('backend.pre-cadastros.reprovar', $reseller), [
                'justificativa' => 'Tentativa de decidir um cadastro que já saiu da fila.',
            ])
            ->assertForbidden();

        $this->assertSame(Reseller::STATUS_APPROVED, $reseller->refresh()->status);
    }

    private function permissaoId(string $chave): int
    {
        /** @var int $id */
        $id = AclPermission::query()->where('key', $chave)->value('id');

        return $id;
    }
}
