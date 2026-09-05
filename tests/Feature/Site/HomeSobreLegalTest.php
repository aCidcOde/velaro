<?php

/*
[Modulo: tests/Feature/Site]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Cobre as telas 1.1 e 1.2 e os documentos legais: conteudo do banco na tela e nenhum preco B2B no HTML.
*/

namespace Tests\Feature\Site;

use App\Models\Product;
use App\Models\ProductCollection;
use App\Models\Setting;
use App\Services\Site\LegalDocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class HomeSobreLegalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // As rotas do Velaro entram no bootstrap so na integracao dos quatro
        // ambientes; ate la o teste as carrega por conta propria.
        if (! Route::has('site.home')) {
            Route::middleware('web')->group(base_path('routes/velaro.php'));
            Route::getRoutes()->refreshNameLookups();
            Route::getRoutes()->refreshActionLookups();
        }
    }

    public function test_home_lista_as_colecoes_ativas(): void
    {
        ProductCollection::factory()->create([
            'name' => 'Coleção Diamante',
            'slug' => 'colecao-diamante',
            'description' => 'Brilho que eterniza seus melhores momentos.',
            'position' => 1,
            'is_active' => true,
        ]);

        ProductCollection::factory()->create([
            'name' => 'Coleção Aposentada',
            'slug' => 'colecao-aposentada',
            'position' => 2,
            'is_active' => false,
        ]);

        $this->get(route('site.home'))
            ->assertOk()
            ->assertSee('Coleção Diamante')
            ->assertSee('Brilho que eterniza seus melhores momentos.')
            ->assertSee(route('site.catalogo', 'colecao-diamante'))
            ->assertDontSee('Coleção Aposentada');
    }

    public function test_home_declara_que_a_plataforma_e_exclusiva_para_lojistas(): void
    {
        $this->get(route('site.home'))
            ->assertOk()
            ->assertSee('Plataforma exclusiva para lojistas e revendedores')
            ->assertSee('Não realizamos vendas diretas ao consumidor final.')
            ->assertSee(route('site.cadastro'));
    }

    public function test_home_nao_expoe_o_custo_b2b_do_catalogo(): void
    {
        ProductCollection::factory()->create(['slug' => 'classica', 'is_active' => true]);

        Product::factory()->create([
            'name' => 'Aliança de teste',
            'slug' => 'alianca-de-teste',
            'price' => 1234.56,
            'is_active' => true,
        ]);

        $html = (string) $this->get(route('site.home'))->assertOk()->getContent();

        $this->assertStringNotContainsString('1234.56', $html);
        $this->assertStringNotContainsString('1.234,56', $html);
        $this->assertStringNotContainsString('R$', $html);
    }

    public function test_rodape_do_site_le_o_atendimento_de_settings(): void
    {
        $this->settingPublica('contact', 'contact.telefone', '+55 (16) 90000-0000');
        $this->settingPublica('contact', 'contact.email', 'atendimento@velaro.test');
        $this->settingPublica('contact', 'contact.horario', 'Segunda a sexta, das 9h às 17h');
        $this->settingPublica('company', 'company.nome', 'Velaro Alianças');

        // O rodapé é do casco: vale para as 13 telas do site, não só para a home.
        foreach (['site.home', 'site.sobre', 'site.privacidade', 'site.termos'] as $rota) {
            $this->get(route($rota))
                ->assertOk()
                ->assertSee('+55 (16) 90000-0000')
                ->assertSee('atendimento@velaro.test')
                ->assertSee('Segunda a sexta, das 9h às 17h')
                ->assertSee('Velaro Alianças');
        }
    }

    public function test_rodape_nao_vaza_parametro_de_contato_nao_publico(): void
    {
        Setting::factory()->create([
            'group' => 'contact',
            'key' => 'contact.telefone',
            'value' => '+55 (16) 91111-1111',
            'type' => 'string',
            'is_public' => false,
        ]);

        $this->get(route('site.home'))
            ->assertOk()
            ->assertDontSee('+55 (16) 91111-1111');
    }

    public function test_sobre_renderiza_o_conteudo_institucional_do_banco(): void
    {
        $this->settingPublica('about', 'about.hero_eyebrow', 'Quem é a Velaro');
        $this->settingPublica('about', 'about.hero_titulo', 'A excelência por trás da Velaro.');
        $this->settingPublica('about', 'about.hero_texto', "Primeiro parágrafo do hero.\n\nSegundo parágrafo do hero.");
        $this->settingPublica('about', 'about.historia', "Primeiro parágrafo da história.\n\nSegundo parágrafo da história.");
        $this->settingPublica('about', 'about.diferenciais', (string) json_encode([
            ['titulo' => 'Fábrica própria', 'texto' => 'Produção 100% própria.'],
        ], JSON_UNESCAPED_UNICODE));
        $this->settingPublica('about', 'about.numeros', (string) json_encode([
            ['titulo' => 'Atendimento nacional', 'texto' => 'Lojistas em todo o Brasil.'],
        ], JSON_UNESCAPED_UNICODE));

        $this->get(route('site.sobre'))
            ->assertOk()
            ->assertSee('Quem é a Velaro')
            ->assertSee('A excelência por trás da Velaro.')
            ->assertSee('Primeiro parágrafo do hero.')
            ->assertSee('Segundo parágrafo do hero.')
            ->assertSee('Primeiro parágrafo da história.')
            ->assertSee('Segundo parágrafo da história.')
            ->assertSee('Fábrica própria')
            ->assertSee('Atendimento nacional')
            ->assertSee(route('site.contato', ['origem' => 'sobre']));
    }

    public function test_sobre_ignora_parametro_nao_publico(): void
    {
        Setting::factory()->create([
            'group' => 'about',
            'key' => 'about.hero_titulo',
            'value' => 'Rascunho interno que não pode vazar',
            'type' => 'string',
            'is_public' => false,
        ]);

        $this->get(route('site.sobre'))
            ->assertOk()
            ->assertDontSee('Rascunho interno que não pode vazar');
    }

    public function test_privacidade_traz_versao_indice_e_identificacao_da_controladora(): void
    {
        $this->settingPublica('company', 'company.razao_social', 'Velaro Alianças Ltda.');
        $this->settingPublica('company', 'company.cnpj', '45.123.456/0001-09');
        $this->settingPublica('company', 'company.endereco', 'Ribeirão Preto/SP');

        $this->get(route('site.privacidade'))
            ->assertOk()
            ->assertSee('Política de Privacidade')
            ->assertSee('Versão '.LegalDocumentService::VERSION.' · vigente desde '.LegalDocumentService::EFFECTIVE_FROM)
            ->assertSee('Velaro Alianças Ltda.')
            ->assertSee('45.123.456/0001-09')
            ->assertSee('Ribeirão Preto/SP')
            ->assertSee('Quem somos e o que esta política cobre')
            ->assertSee('Alterações desta política')
            ->assertSee(route('site.termos'));
    }

    public function test_termos_trazem_as_regras_da_relacao_b2b(): void
    {
        $this->get(route('site.termos'))
            ->assertOk()
            ->assertSee('Termos de Uso')
            ->assertSee('Objeto e aceitação')
            ->assertSee('Alterações, lei aplicável e foro')
            ->assertSee('O catálogo público não exibe preço.')
            ->assertSee(route('site.privacidade'));
    }

    public function test_documentos_legais_nao_exigem_autenticacao(): void
    {
        $this->assertGuest();

        $this->get(route('site.privacidade'))->assertOk();
        $this->get(route('site.termos'))->assertOk();
        $this->get(route('site.home'))->assertOk();
        $this->get(route('site.sobre'))->assertOk();
    }

    private function settingPublica(string $group, string $key, string $value): void
    {
        Setting::factory()->create([
            'group' => $group,
            'key' => $key,
            'value' => $value,
            'type' => 'string',
            'is_public' => true,
        ]);
    }
}
