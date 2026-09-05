<?php

/*
[Modulo: tests/Feature/Portal]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Cobre "Vitrine para clientes": abre a loja do proprio lojista, nunca a de outro, e desvia quando ela nao foi publicada.
*/

namespace Tests\Feature\Portal;

use App\Models\Reseller;
use App\Models\ResellerStore;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * O item de menu não é uma tela: é a porta para a vitrine white label do próprio
 * lojista. Como a loja de destino sai do escopo do revendedor autenticado e não
 * da URL, não existe caminho por onde um lojista abra a vitrine de outro por
 * aqui — e é isso que os casos abaixo prendem.
 */
class VitrineRedirectTest extends TestCase
{
    use RefreshDatabase;

    private Reseller $tomazelli;

    private Reseller $vizinho;

    private User $lojista;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tomazelli = Reseller::factory()->approved()->create();
        $this->vizinho = Reseller::factory()->approved()->create();
        $this->lojista = User::factory()->forReseller($this->tomazelli)->create();
    }

    public function test_abre_a_vitrine_publicada_do_proprio_lojista(): void
    {
        $minha = ResellerStore::factory()->published()->create([
            'reseller_id' => $this->tomazelli->getKey(),
            'slug' => 'tomazelli-aliancas',
        ]);

        $this->actingAs($this->lojista)
            ->get(route('portal.vitrine'))
            ->assertRedirect(route('vitrine.index', $minha));
    }

    public function test_nunca_abre_a_vitrine_de_outro_lojista(): void
    {
        // As duas lojas existem e as duas estão publicadas. A do vizinho é a que
        // não pode ser o destino — nem por sorte, nem por ordem de criação.
        ResellerStore::factory()->published()->create([
            'reseller_id' => $this->vizinho->getKey(),
            'slug' => 'alianca-e-cia',
        ]);

        $minha = ResellerStore::factory()->published()->create([
            'reseller_id' => $this->tomazelli->getKey(),
            'slug' => 'tomazelli-aliancas',
        ]);

        $resposta = $this->actingAs($this->lojista)->get(route('portal.vitrine'));

        $resposta->assertRedirect(route('vitrine.index', $minha));
        $this->assertStringNotContainsString('alianca-e-cia', (string) $resposta->headers->get('Location'));
    }

    public function test_lojista_sem_vitrine_vai_para_a_personalizacao_com_aviso(): void
    {
        $resposta = $this->actingAs($this->lojista)->get(route('portal.vitrine'));

        $resposta->assertRedirect(route('portal.loja.edit'));
        $resposta->assertSessionHas('status');
    }

    public function test_vitrine_criada_mas_nao_publicada_tambem_desvia(): void
    {
        // Loja existe, tem slug e ainda não foi ao ar: quem está montando a
        // vitrine é levado ao lugar onde ela é publicada, e não a um erro.
        ResellerStore::factory()->create([
            'reseller_id' => $this->tomazelli->getKey(),
            'slug' => 'tomazelli-aliancas',
            'is_active' => false,
            'published_at' => null,
        ]);

        $this->actingAs($this->lojista)
            ->get(route('portal.vitrine'))
            ->assertRedirect(route('portal.loja.edit'));
    }

    public function test_cada_lojista_cai_na_propria_loja(): void
    {
        $minha = ResellerStore::factory()->published()->create([
            'reseller_id' => $this->tomazelli->getKey(),
            'slug' => 'tomazelli-aliancas',
        ]);
        $dele = ResellerStore::factory()->published()->create([
            'reseller_id' => $this->vizinho->getKey(),
            'slug' => 'alianca-e-cia',
        ]);

        $outroLojista = User::factory()->forReseller($this->vizinho)->create();

        $this->entrarComo($this->lojista)
            ->get(route('portal.vitrine'))
            ->assertRedirect(route('vitrine.index', $minha));

        $this->entrarComo($outroLojista)
            ->get(route('portal.vitrine'))
            ->assertRedirect(route('vitrine.index', $dele));
    }

    /**
     * Troca o lojista logado no meio do caso.
     *
     * `ResellerScope` é um binding `scoped`, e duas requisições de teste dividem
     * o mesmo container — sem soltar a instância, a segunda responderia com o
     * escopo da primeira. Em produção não há o que soltar: sob FPM cada
     * requisição sobe um container novo, e sob Octane e no worker de fila o
     * próprio framework chama `forgetScopedInstances()` entre uma e outra.
     */
    private function entrarComo(User $usuario): self
    {
        $this->app->forgetScopedInstances();

        return $this->actingAs($usuario);
    }
}
