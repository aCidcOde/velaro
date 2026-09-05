<?php

/*
[Modulo: tests/Feature/Portal]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Cobre a tela 2.6: identidade da vitrine, unicidade de slug e dominio, publicacao e isolamento entre lojistas.
*/

namespace Tests\Feature\Portal;

use App\Models\Product;
use App\Models\Reseller;
use App\Models\ResellerPriceSetting;
use App\Models\ResellerStore;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LojaTest extends TestCase
{
    use RefreshDatabase;

    private Reseller $tomazelli;

    private Reseller $vizinho;

    private User $lojista;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tomazelli = Reseller::factory()->approved()->create(['trade_name' => 'Tomazelli Alianças']);
        $this->vizinho = Reseller::factory()->approved()->create(['trade_name' => 'Aliança & Cia']);
        $this->lojista = User::factory()->forReseller($this->tomazelli)->create();
    }

    /**
     * Payload completo do formulário. A tela grava tudo de uma vez — inclusive os
     * toggles, que são checkbox: ausente significa desligado.
     *
     * @param  array<string, mixed>  $troca
     * @return array<string, mixed>
     */
    private function formulario(array $troca = []): array
    {
        return array_merge([
            'name' => 'Tomazelli Alianças',
            'slogan' => 'Símbolo de amor. Promessa para a vida toda.',
            'slug' => 'tomazelli-aliancas',
            'domain' => 'tomazellialiancas.com.br',
            'phone' => '(11) 98888-2020',
            'whatsapp' => '(11) 98888-2020',
            'email' => 'contato@tomazellialiancas.com.br',
            'address' => 'Rua das Alianças, 123 - Centro, São Paulo - SP',
            'color_primary' => '#800020',
            'color_secondary' => '#B8860B',
            'color_background' => '#FFFFFF',
            'color_text' => '#1A1A1A',
            'own_brand_only' => '1',
            'hide_supplier_brand' => '1',
            'show_prices' => '1',
            'pickup_only' => '1',
            'payment_in_store' => '1',
            'pricing_model' => ResellerPriceSetting::PRICING_MODEL_MULTIPLIER,
            'multiplier' => '3.6',
            'apply_to_all' => '1',
            'allow_manual_override' => '1',
            'allow_promotional_prices' => '1',
        ], $troca);
    }

    public function test_a_tela_abre_com_os_campos_do_prototipo_mesmo_sem_loja_salva(): void
    {
        // Quem nunca salvou não pode encontrar um formulário em branco: o nome e o
        // contato já são conhecidos desde a aprovação do cadastro.
        $this->assertNull($this->tomazelli->store()->first());

        $resposta = $this->actingAs($this->lojista)->get(route('portal.loja.edit'));

        $resposta->assertOk();
        $resposta->assertSee('Personalização da loja');
        $resposta->assertSee('① Identidade da loja', false);
        $resposta->assertSee('② Regra de preços', false);
        $resposta->assertSee('Tomazelli Alianças', false);
        $resposta->assertSee('name="slug"', false);
        $resposta->assertSee('name="domain"', false);
        $resposta->assertSee('name="color_primary"', false);
        $resposta->assertSee('#800020');
        $resposta->assertSee('Ocultar marca do fornecedor');
        $resposta->assertSee('Pré-visualização da loja');

        // Abrir a tela é leitura: não cria linha nenhuma.
        $this->assertDatabaseCount('reseller_stores', 0);
        $this->assertDatabaseCount('reseller_price_settings', 0);
    }

    public function test_salvar_grava_identidade_cores_e_toggles(): void
    {
        $resposta = $this->actingAs($this->lojista)
            ->put(route('portal.loja.update'), $this->formulario(['action' => 'salvar']));

        $resposta->assertRedirect(route('portal.loja.edit'));
        $resposta->assertSessionHas('status');

        $loja = $this->tomazelli->store()->first();

        $this->assertInstanceOf(ResellerStore::class, $loja);
        $this->assertSame('Tomazelli Alianças', $loja->name);
        $this->assertSame('tomazelli-aliancas', $loja->slug);
        $this->assertSame('tomazellialiancas.com.br', $loja->domain);
        $this->assertSame('#800020', $loja->color_primary);
        $this->assertSame('#B8860B', $loja->color_secondary);
        $this->assertTrue($loja->own_brand_only);
        $this->assertTrue($loja->hide_supplier_brand);

        // Salvar não publica: a vitrine só entra no ar pelo botão "Publicar".
        $this->assertFalse($loja->is_active);
        $this->assertNull($loja->published_at);
    }

    public function test_toggle_desmarcado_desliga_a_coluna(): void
    {
        $this->actingAs($this->lojista)->put(route('portal.loja.update'), $this->formulario());

        // Checkbox ausente no POST é "desligado", e não "não mexeu": sem isso o
        // lojista nunca conseguiria desligar um toggle já ligado.
        $semToggles = $this->formulario();
        unset($semToggles['own_brand_only'], $semToggles['hide_supplier_brand'], $semToggles['show_prices']);

        $this->actingAs($this->lojista)->put(route('portal.loja.update'), $semToggles);

        $loja = $this->tomazelli->store()->first();

        $this->assertInstanceOf(ResellerStore::class, $loja);
        $this->assertFalse($loja->own_brand_only);
        $this->assertFalse($loja->hide_supplier_brand);
        $this->assertFalse($loja->show_prices);
        $this->assertTrue($loja->pickup_only);
    }

    public function test_publicar_liga_a_vitrine_e_carimba_a_data(): void
    {
        $resposta = $this->actingAs($this->lojista)
            ->put(route('portal.loja.update'), $this->formulario(['action' => 'publicar']));

        $resposta->assertRedirect(route('portal.loja.edit'));

        $loja = $this->tomazelli->store()->first();

        $this->assertInstanceOf(ResellerStore::class, $loja);
        $this->assertTrue($loja->is_active);
        $this->assertNotNull($loja->published_at);

        $primeiraPublicacao = $loja->published_at;

        // Republicar não reescreve a data da primeira publicação.
        $this->actingAs($this->lojista)->put(route('portal.loja.update'), $this->formulario(['action' => 'publicar']));

        $this->assertTrue($primeiraPublicacao->equalTo($this->tomazelli->store()->first()?->published_at));
    }

    public function test_o_proprio_registro_e_ignorado_na_unicidade_de_slug_e_dominio(): void
    {
        // Sem o `ignore` no `unique`, o segundo save colidiria com a própria loja
        // e o lojista ficaria sem conseguir mexer em mais nada da tela.
        $this->actingAs($this->lojista)->put(route('portal.loja.update'), $this->formulario());

        $resposta = $this->actingAs($this->lojista)
            ->put(route('portal.loja.update'), $this->formulario(['slogan' => 'Outro slogan']));

        $resposta->assertSessionHasNoErrors();
        $this->assertSame('Outro slogan', $this->tomazelli->store()->first()?->slogan);
    }

    public function test_slug_e_dominio_ja_usados_por_outro_lojista_sao_recusados(): void
    {
        ResellerStore::factory()->create([
            'reseller_id' => $this->vizinho->getKey(),
            'slug' => 'tomazelli-aliancas',
            'domain' => 'tomazellialiancas.com.br',
        ]);

        $resposta = $this->actingAs($this->lojista)
            ->put(route('portal.loja.update'), $this->formulario());

        $resposta->assertSessionHasErrors(['slug', 'domain']);
        $this->assertNull($this->tomazelli->store()->first());
    }

    public function test_o_dominio_e_normalizado_antes_de_bater_na_coluna_unica(): void
    {
        // `https://Loja.com.br/` e `loja.com.br` são o mesmo domínio; sem a
        // normalização a coluna UNIQUE aceitaria os dois e a vitrine teria duas
        // portas para lugares diferentes.
        $this->actingAs($this->lojista)
            ->put(route('portal.loja.update'), $this->formulario(['domain' => 'https://Tomazelliliancas.com.br/']));

        $this->assertSame('tomazelliliancas.com.br', $this->tomazelli->store()->first()?->domain);
    }

    public function test_campos_invalidos_reprovam_a_gravacao(): void
    {
        $resposta = $this->actingAs($this->lojista)->put(route('portal.loja.update'), $this->formulario([
            'name' => '',
            'slug' => 'nao pode ter espaço',
            'color_primary' => 'vermelho',
            'email' => 'nao-e-email',
            'multiplier' => '0.2',
        ]));

        $resposta->assertSessionHasErrors(['name', 'slug', 'color_primary', 'email', 'multiplier']);
        $this->assertDatabaseCount('reseller_stores', 0);
    }

    public function test_a_logo_enviada_e_guardada_e_o_save_seguinte_nao_a_apaga(): void
    {
        Storage::fake('public');

        $this->actingAs($this->lojista)->put(route('portal.loja.update'), $this->formulario([
            'logo' => UploadedFile::fake()->image('logo.png', 400, 400),
        ]));

        $caminho = $this->tomazelli->store()->first()?->logo_path;

        $this->assertIsString($caminho);
        Storage::disk('public')->assertExists($caminho);

        // Salvar de novo sem reenviar arquivo não pode apagar a logo que já está
        // no ar.
        $this->actingAs($this->lojista)->put(route('portal.loja.update'), $this->formulario());

        $this->assertSame($caminho, $this->tomazelli->store()->first()?->logo_path);
    }

    public function test_o_bloco_de_preco_grava_na_configuracao_do_lojista(): void
    {
        // O bloco ② é `reseller_price_settings` — a mesma linha da tela 2.7. Se
        // cada tela tivesse a sua cópia, a vitrine praticaria um preço e o portal
        // mostraria outro.
        $this->actingAs($this->lojista)->put(route('portal.loja.update'), $this->formulario([
            'multiplier' => '4.2',
            'pricing_model' => ResellerPriceSetting::PRICING_MODEL_MULTIPLIER,
        ]));

        $configuracao = $this->tomazelli->priceSetting()->first();

        $this->assertInstanceOf(ResellerPriceSetting::class, $configuracao);
        $this->assertSame('4.20', (string) $configuracao->multiplier);

        $this->actingAs($this->lojista)->get(route('portal.precos.edit'))->assertSee('4.20', false);
    }

    public function test_a_previa_mostra_o_preco_ao_consumidor_e_nunca_o_custo_velaro(): void
    {
        Product::factory()->create(['name' => 'Aliança Clássica 4mm', 'price' => 100.00, 'is_active' => true]);

        $this->actingAs($this->lojista)->put(route('portal.loja.update'), $this->formulario(['multiplier' => '3']));

        $resposta = $this->actingAs($this->lojista)->get(route('portal.loja.edit'));

        $resposta->assertOk();
        // 100 × 3 = 300, arredondado para cima em 0,99.
        $resposta->assertSee('R$ 300,99');
        // O custo B2B não aparece na prévia: ela é o que o consumidor final vê.
        $resposta->assertDontSee('R$ 100,00');
    }

    public function test_com_precos_ocultos_a_previa_nao_mostra_valor(): void
    {
        Product::factory()->create(['name' => 'Aliança Clássica 4mm', 'price' => 100.00, 'is_active' => true]);

        $semPreco = $this->formulario();
        unset($semPreco['show_prices']);

        $this->actingAs($this->lojista)->put(route('portal.loja.update'), $semPreco);

        $resposta = $this->actingAs($this->lojista)->get(route('portal.loja.edit'));

        $resposta->assertOk();
        $resposta->assertDontSee('R$ 300,99');
        $resposta->assertSee('Consulte');
    }

    public function test_a_tela_so_alcanca_a_loja_do_proprio_lojista(): void
    {
        $daVizinha = ResellerStore::factory()->create([
            'reseller_id' => $this->vizinho->getKey(),
            'name' => 'Aliança & Cia',
            'slogan' => 'O slogan do concorrente',
            'slug' => 'alianca-e-cia',
        ]);

        $resposta = $this->actingAs($this->lojista)->get(route('portal.loja.edit'));

        $resposta->assertOk();
        $resposta->assertDontSee('O slogan do concorrente');
        $resposta->assertDontSee('alianca-e-cia');

        // E gravar não pode encostar na linha do vizinho.
        $this->actingAs($this->lojista)->put(route('portal.loja.update'), $this->formulario());

        $this->assertSame('O slogan do concorrente', $daVizinha->refresh()->slogan);
        $this->assertSame('alianca-e-cia', $daVizinha->slug);
    }

    public function test_quem_nao_e_lojista_aprovado_nao_entra_na_tela(): void
    {
        $pendente = User::factory()->forReseller(Reseller::factory()->pending()->create())->create();

        $this->actingAs($pendente)->get(route('portal.loja.edit'))->assertForbidden();
    }
}
