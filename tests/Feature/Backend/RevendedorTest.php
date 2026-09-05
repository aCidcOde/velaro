<?php

/*
[Modulo: tests/Feature/Backend]
@Author: André Gomes ( @acidcode )
@since 2026-09-06
Cobre a tela 3.10: listagem, permissoes granulares e o cadastro manual com log.
*/

namespace Tests\Feature\Backend;

use App\Models\AclPermission;
use App\Models\Reseller;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RevendedorTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_lista_mostra_os_revendedores_da_base(): void
    {
        $admin = $this->createBackendAdmin();
        $ativo = Reseller::factory()->create(['status' => Reseller::STATUS_APPROVED]);

        $this->actingAs($admin)
            ->get(route('backend.revendedores.index'))
            ->assertOk()
            ->assertSee($ativo->trade_name);
    }

    public function test_o_filtro_de_status_restringe_a_lista(): void
    {
        $admin = $this->createBackendAdmin();
        $ativo = Reseller::factory()->create(['status' => Reseller::STATUS_APPROVED]);
        $pendente = Reseller::factory()->create(['status' => Reseller::STATUS_PENDING]);

        $this->actingAs($admin)
            ->get(route('backend.revendedores.index', ['status' => Reseller::STATUS_APPROVED]))
            ->assertOk()
            ->assertSee($ativo->trade_name)
            ->assertDontSee($pendente->trade_name);
    }

    public function test_usuario_sem_a_permissao_nao_ve_a_base(): void
    {
        $admin = $this->createBackendAdmin();
        $admin->permissionOverrides()->updateOrCreate(
            ['permission_id' => $this->permissaoId('velaro.resellers.view')],
            ['is_allowed' => false],
        );

        $this->actingAs($admin->refresh())
            ->get(route('backend.revendedores.index'))
            ->assertForbidden();
    }

    public function test_cadastro_manual_nasce_pendente_e_gera_log(): void
    {
        $admin = $this->createBackendAdmin();

        $this->actingAs($admin)
            ->post(route('backend.revendedores.store'), $this->dadosValidos())
            ->assertRedirect();

        $reseller = Reseller::query()->where('cnpj', '12.345.678/0001-90')->firstOrFail();

        // Regra 2: o cadastro manual nao passa pela fila da 3.11, mas tambem nao
        // nasce aprovado — a aprovacao e a acao seguinte, com justificativa.
        $this->assertSame(Reseller::STATUS_PENDING, $reseller->status);
        $this->assertSame(Reseller::REGISTRATION_TYPE_MANUAL, $reseller->registration_type);
        $this->assertNotNull($reseller->protocol);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'velaro.reseller.created',
            'target_id' => $reseller->id,
        ]);
        $this->assertDatabaseHas('reseller_status_events', [
            'reseller_id' => $reseller->id,
            'to_status' => Reseller::STATUS_PENDING,
            'actor_id' => $admin->id,
        ]);
    }

    public function test_cadastro_manual_recusa_cnpj_repetido(): void
    {
        $admin = $this->createBackendAdmin();
        Reseller::factory()->create(['cnpj' => '12.345.678/0001-90']);

        $this->actingAs($admin)
            ->from(route('backend.revendedores.index'))
            ->post(route('backend.revendedores.store'), $this->dadosValidos())
            ->assertSessionHasErrors('cnpj');

        $this->assertSame(1, Reseller::query()->where('cnpj', '12.345.678/0001-90')->count());
    }

    public function test_usuario_sem_permissao_de_criar_nao_cadastra(): void
    {
        $admin = $this->createBackendAdmin();
        $admin->permissionOverrides()->updateOrCreate(
            ['permission_id' => $this->permissaoId('velaro.resellers.create')],
            ['is_allowed' => false],
        );

        $this->actingAs($admin->refresh())
            ->post(route('backend.revendedores.store'), $this->dadosValidos())
            ->assertForbidden();

        $this->assertSame(0, Reseller::query()->where('cnpj', '12.345.678/0001-90')->count());
    }

    public function test_a_ficha_do_revendedor_abre(): void
    {
        $admin = $this->createBackendAdmin();
        $reseller = Reseller::factory()->create(['status' => Reseller::STATUS_APPROVED]);

        $this->actingAs($admin)
            ->get(route('backend.revendedores.show', $reseller))
            ->assertOk()
            ->assertSee($reseller->legal_name)
            ->assertSee($reseller->cnpj);
    }

    /**
     * @return array<string, mixed>
     */
    private function dadosValidos(): array
    {
        return [
            'trade_name' => 'Tomazelli Alianças',
            'legal_name' => 'Tomazelli Comércio de Joias LTDA',
            'cnpj' => '12.345.678/0001-90',
            'contact_name' => 'André Tomazelli',
            'email' => 'contato@tomazelli.test',
            'phone' => '(17) 99999-0000',
            'postal_code' => '15015-000',
            'street' => 'Rua das Alianças',
            'street_number' => '123',
            'district' => 'Centro',
            'city' => 'São José do Rio Preto',
            'state' => 'SP',
        ];
    }

    private function permissaoId(string $chave): int
    {
        /** @var int $id */
        $id = AclPermission::query()->where('key', $chave)->value('id');

        return $id;
    }
}
