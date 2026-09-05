<?php

/*
[Modulo: tests/Feature/Portal]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Cobre a central de ajuda do portal: FAQ da plataforma, biblioteca publicada, busca e o estado sem conteudo.
*/

namespace Tests\Feature\Portal;

use App\Models\HelpArticle;
use App\Models\HelpCategory;
use App\Models\Reseller;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A central tem duas metades: o FAQ operacional, versionado no código, e a
 * biblioteca editorial, vinda de `help_categories`/`help_articles`. Os casos
 * abaixo cobrem as duas — e o estado em que a segunda ainda está vazia, que é
 * como uma base recém-instalada começa.
 */
class CentralAjudaTest extends TestCase
{
    use RefreshDatabase;

    private User $lojista;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lojista = User::factory()
            ->forReseller(Reseller::factory()->approved()->create())
            ->create();
    }

    public function test_a_central_abre_com_o_faq_operacional_da_plataforma(): void
    {
        $resposta = $this->actingAs($this->lojista)->get(route('portal.ajuda'));

        $resposta->assertOk();
        $resposta->assertSee('Central de ajuda');
        $resposta->assertSee('Quando o meu pedido entra em produção?');
        $resposta->assertSee('Perguntas frequentes');
        // A central é do lojista: o consumidor final não tem login na plataforma.
        $resposta->assertSee('não tem login na plataforma', false);
    }

    public function test_base_sem_artigo_publicado_diz_que_a_biblioteca_esta_vazia(): void
    {
        $resposta = $this->actingAs($this->lojista)->get(route('portal.ajuda'));

        $resposta->assertOk();
        $resposta->assertSee('biblioteca de artigos ainda está sendo publicada', false);
    }

    public function test_a_biblioteca_publicada_aparece_com_categoria_guia_e_video(): void
    {
        $categoria = HelpCategory::factory()->create([
            'name' => 'Preços e margens',
            'slug' => 'precos-e-margens',
            'position' => 1,
        ]);

        HelpArticle::factory()->create([
            'help_category_id' => $categoria->getKey(),
            'type' => HelpArticle::TYPE_GUIDE,
            'title' => 'Como definir a margem e o preço de venda da sua loja',
            'slug' => 'como-definir-a-margem',
            'body' => '<p>O preço que o seu cliente paga é definido por você.</p>',
            'file_path' => null,
            'position' => 0,
        ]);

        HelpArticle::factory()->guide()->create([
            'help_category_id' => $categoria->getKey(),
            'title' => 'Manual do Portal do Lojista',
            'slug' => 'manual-do-portal',
            'position' => 1,
        ]);

        HelpArticle::factory()->video()->create([
            'help_category_id' => $categoria->getKey(),
            'title' => 'Tour pelo Portal do Lojista',
            'slug' => 'tour-pelo-portal',
            'position' => 2,
        ]);

        $resposta = $this->actingAs($this->lojista)->get(route('portal.ajuda'));

        $resposta->assertOk();
        $resposta->assertSee('Preços e margens');
        $resposta->assertSee('Como definir a margem e o preço de venda da sua loja');
        $resposta->assertSee('O preço que o seu cliente paga é definido por você.', false);
        $resposta->assertSee('Guias e manuais');
        $resposta->assertSee('Manual do Portal do Lojista');
        $resposta->assertSee('Vídeos tutoriais');
        $resposta->assertSee('Tour pelo Portal do Lojista');
        $resposta->assertSee('min de leitura');
    }

    public function test_artigo_nao_publicado_e_categoria_inativa_ficam_de_fora(): void
    {
        $inativa = HelpCategory::factory()->inactive()->create(['name' => 'Categoria fora do ar']);
        HelpArticle::factory()->create([
            'help_category_id' => $inativa->getKey(),
            'title' => 'Artigo de categoria desligada',
            'slug' => 'artigo-de-categoria-desligada',
        ]);

        $categoria = HelpCategory::factory()->create(['name' => 'Financeiro e pagamentos']);
        HelpArticle::factory()->unpublished()->create([
            'help_category_id' => $categoria->getKey(),
            'type' => HelpArticle::TYPE_GUIDE,
            'title' => 'Rascunho que ninguém deve ver',
            'slug' => 'rascunho-invisivel',
            'body' => '<p>Ainda em revisão.</p>',
        ]);

        $resposta = $this->actingAs($this->lojista)->get(route('portal.ajuda'));

        $resposta->assertOk();
        $resposta->assertDontSee('Categoria fora do ar');
        $resposta->assertDontSee('Rascunho que ninguém deve ver');
        $resposta->assertDontSee('Ainda em revisão.');
    }

    public function test_a_busca_encontra_no_artigo_e_no_faq(): void
    {
        $categoria = HelpCategory::factory()->create(['name' => 'Financeiro e pagamentos']);

        HelpArticle::factory()->create([
            'help_category_id' => $categoria->getKey(),
            'type' => HelpArticle::TYPE_GUIDE,
            'title' => 'Como funciona o lote semanal de pagamento',
            'slug' => 'lote-semanal',
            'body' => '<p>A cobrança fecha toda sexta-feira.</p>',
        ]);

        $resposta = $this->actingAs($this->lojista)->get(route('portal.ajuda', ['q' => 'lote']));

        $resposta->assertOk();
        $resposta->assertSee('Resultados para');
        $resposta->assertSee('Como funciona o lote semanal de pagamento');
        // O FAQ operacional responde à mesma busca — metade das dúvidas está lá.
        $resposta->assertSee('Posso pagar um pedido isolado, fora do lote?');
    }

    public function test_busca_sem_resultado_convida_a_abrir_chamado(): void
    {
        $resposta = $this->actingAs($this->lojista)->get(route('portal.ajuda', ['q' => 'zzzz-nao-existe']));

        $resposta->assertOk();
        $resposta->assertSee('Nada encontrado para esse termo.');
    }

    public function test_termo_gigante_e_podado_em_vez_de_recusado(): void
    {
        $resposta = $this->actingAs($this->lojista)->get(route('portal.ajuda', ['q' => str_repeat('a', 500)]));

        $resposta->assertOk();
    }

    public function test_quem_nao_e_revendedor_aprovado_nao_entra_na_central(): void
    {
        $this->get(route('portal.ajuda'))->assertRedirect(route('login'));

        $pendente = User::factory()->forReseller(Reseller::factory()->pending()->create())->create();

        $this->actingAs($pendente)->get(route('portal.ajuda'))->assertForbidden();
    }
}
