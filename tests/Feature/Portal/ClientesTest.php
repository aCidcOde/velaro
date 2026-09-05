<?php

/*
[Modulo: tests/Feature/Portal]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Cobre a tela 2.3: carteira filtravel, ficha com relacionamento e LGPD, e o isolamento entre lojistas.
*/

namespace Tests\Feature\Portal;

use App\Models\Customer;
use App\Models\CustomerConsent;
use App\Models\Order;
use App\Models\Reseller;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Tela 2.3 — Clientes / CRM.
 *
 * Todo caso monta **dois** revendedores com dado da mesma forma. Sem vizinho na
 * base, "não vejo o cliente do outro" é uma afirmação que nenhum teste consegue
 * derrubar — e é justamente a regra central do ambiente.
 */
class ClientesTest extends TestCase
{
    use RefreshDatabase;

    private Reseller $tomazelli;

    private Reseller $vizinho;

    private User $lojista;

    private Customer $maria;

    private Customer $doVizinho;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tomazelli = Reseller::factory()->approved()->create(['trade_name' => 'Tomazelli Alianças']);
        $this->vizinho = Reseller::factory()->approved()->create(['trade_name' => 'Aliança & Cia']);

        $this->lojista = User::factory()->forReseller($this->tomazelli)->create();

        $this->maria = $this->cliente($this->tomazelli, [
            'name' => 'Maria Silva',
            'document' => '123.456.789-00',
            'email' => 'maria.silva@email.com',
            'phone' => '(11) 98765-4321',
            'city' => 'São Paulo',
            'state' => 'SP',
            'birth_date' => '1994-03-18',
            'wedding_date' => '2026-08-22',
            'relationship_date' => '2021-02-14',
        ]);

        $this->doVizinho = $this->cliente($this->vizinho, [
            'name' => 'Beatriz Nogueira',
            'document' => '208.417.665-32',
            'email' => 'beatriz.nogueira@email.com',
            'city' => 'Curitiba',
            'state' => 'PR',
        ]);
    }

    // ─────────────────────────── caminho feliz ───────────────────────────

    public function test_a_carteira_lista_o_cliente_com_cidade_documento_e_ultimo_pedido(): void
    {
        $pedido = $this->pedido($this->tomazelli, $this->maria, ['created_at' => Carbon::now()->subDays(3)]);

        $resposta = $this->actingAs($this->lojista)->get(route('portal.clientes.index'));

        $resposta->assertOk()
            ->assertSee('Maria Silva')
            ->assertSee('123.456.789-00')
            ->assertSee('São Paulo / SP')
            ->assertSee('maria.silva@email.com')
            ->assertSee('Pedido #'.$pedido->public_number);
    }

    public function test_os_quatro_kpis_contam_so_a_carteira_do_lojista(): void
    {
        $this->cliente($this->tomazelli, ['name' => 'João Santos']);
        $this->cliente($this->vizinho, ['name' => 'Marcelo Camargo']);

        $resposta = $this->actingAs($this->lojista)->get(route('portal.clientes.index'));

        $resposta->assertOk()
            ->assertSee('Clientes cadastrados')
            ->assertSee('Clientes ativos')
            ->assertSee('Pedidos em aberto')
            ->assertSee('Último cadastro');

        // Dois clientes na carteira do Tomazelli; o do vizinho não entra na conta.
        $this->assertSame(2, $this->kpi($resposta->getContent(), 'Clientes cadastrados'));
    }

    public function test_a_busca_encontra_por_nome_documento_e_email(): void
    {
        $this->cliente($this->tomazelli, ['name' => 'João Santos', 'document' => '987.654.321-00', 'email' => 'joao@email.com']);

        foreach (['Maria', '123.456.789-00', 'maria.silva@email.com', '(11) 98765-4321'] as $busca) {
            $this->actingAs($this->lojista)
                ->get(route('portal.clientes.index', ['q' => $busca]))
                ->assertOk()
                ->assertSee('Maria Silva')
                ->assertDontSee('João Santos');
        }
    }

    public function test_o_filtro_de_situacao_separa_quem_comprou_na_janela_de_atividade(): void
    {
        $antigo = $this->cliente($this->tomazelli, ['name' => 'Cliente Antigo']);

        $this->pedido($this->tomazelli, $this->maria, ['created_at' => Carbon::now()->subDays(10)]);
        $this->pedido($this->tomazelli, $antigo, ['created_at' => Carbon::now()->subDays(400)]);

        $this->actingAs($this->lojista)
            ->get(route('portal.clientes.index', ['situacao' => 'ativo']))
            ->assertOk()
            ->assertSee('Maria Silva')
            ->assertDontSee('Cliente Antigo');

        $this->actingAs($this->lojista)
            ->get(route('portal.clientes.index', ['situacao' => 'inativo']))
            ->assertOk()
            ->assertSee('Cliente Antigo')
            ->assertDontSee('Maria Silva');
    }

    public function test_o_filtro_de_cidade_leva_as_duas_partes_do_local(): void
    {
        $this->cliente($this->tomazelli, ['name' => 'Cliente Paranaense', 'city' => 'Curitiba', 'state' => 'PR']);

        $this->actingAs($this->lojista)
            ->get(route('portal.clientes.index', ['local' => 'São Paulo|SP']))
            ->assertOk()
            ->assertSee('Maria Silva')
            ->assertDontSee('Cliente Paranaense');
    }

    public function test_o_filtro_de_periodo_corta_pelo_cadastro(): void
    {
        $velho = $this->cliente($this->tomazelli, ['name' => 'Cadastro Velho']);
        $velho->forceFill(['created_at' => Carbon::now()->subDays(200)])->save();

        $this->actingAs($this->lojista)
            ->get(route('portal.clientes.index', ['periodo' => 30]))
            ->assertOk()
            ->assertSee('Maria Silva')
            ->assertDontSee('Cadastro Velho');
    }

    // ─────────────────────────── validação ───────────────────────────

    public function test_filtro_invalido_devolve_a_carteira_em_vez_de_422(): void
    {
        // A carteira é a tela de trabalho do balcão: link velho ou parâmetro
        // torto tem de abrir a lista, não uma tela de erro.
        $resposta = $this->actingAs($this->lojista)->get(route('portal.clientes.index', [
            'situacao' => 'Ativo!!',
            'periodo' => 'ontem',
            'page' => '-4',
            'q' => str_repeat('a', 400),
        ]));

        $resposta->assertOk()->assertSee('Clientes cadastrados');
    }

    public function test_a_busca_sem_resultado_mostra_o_estado_vazio_e_nao_a_carteira_inteira(): void
    {
        $this->actingAs($this->lojista)
            ->get(route('portal.clientes.index', ['q' => 'ninguém com esse nome']))
            ->assertOk()
            ->assertSee('Nenhum cliente com esses filtros')
            ->assertDontSee('Maria Silva');
    }

    // ─────────────────────────── ficha ───────────────────────────

    public function test_a_ficha_mostra_cadastro_datas_de_relacionamento_e_historico(): void
    {
        $pedido = $this->pedido($this->tomazelli, $this->maria, ['total_amount' => 485.00]);

        $resposta = $this->actingAs($this->lojista)->get(route('portal.clientes.show', $this->maria));

        $resposta->assertOk()
            ->assertSee('Maria Silva')
            ->assertSee('123.456.789-00')
            ->assertSee('Aniversário de casamento')
            ->assertSee('Início do namoro')
            ->assertSee('22/08/2026')
            ->assertSee('R$ 485,00')
            ->assertSee('#'.$pedido->public_number);
    }

    public function test_a_ficha_soma_apenas_os_pedidos_do_proprio_revendedor(): void
    {
        // Um pedido do MESMO cliente, mas registrado por outro revendedor. O
        // schema permite (cliente e pedido têm dono próprio) e a ficha não pode
        // contá-lo: seria o faturamento do concorrente aparecendo aqui.
        $this->pedido($this->tomazelli, $this->maria, ['total_amount' => 100.00]);
        $doVizinho = $this->pedido($this->vizinho, $this->maria, ['total_amount' => 900.00]);

        $resposta = $this->actingAs($this->lojista)->get(route('portal.clientes.show', $this->maria));

        $resposta->assertOk()
            ->assertSee('R$ 100,00')
            ->assertDontSee('R$ 900,00')
            ->assertDontSee($doVizinho->public_number);
    }

    // ─────────────────────────── regra LGPD ───────────────────────────

    public function test_com_consentimento_de_marketing_as_datas_alimentam_campanha(): void
    {
        CustomerConsent::factory()->marketing()->create([
            'customer_id' => $this->maria->getKey(),
            'granted' => true,
            'granted_at' => Carbon::now()->subMonth(),
            'revoked_at' => null,
        ]);

        $this->actingAs($this->lojista)
            ->get(route('portal.clientes.show', $this->maria))
            ->assertOk()
            ->assertSee('Campanhas em datas especiais')
            ->assertSee('Liberadas')
            ->assertSee('Consentimento de marketing registrado');
    }

    public function test_consentimento_revogado_bloqueia_a_campanha_e_nao_lista_data_nenhuma(): void
    {
        // A violação da regra 1: o consentimento existiu e foi retirado. A data de
        // casamento continua no cadastro, mas não pode mais alimentar campanha.
        CustomerConsent::factory()->marketing()->create([
            'customer_id' => $this->maria->getKey(),
            'granted' => false,
            'granted_at' => Carbon::now()->subMonths(6),
            'revoked_at' => Carbon::now()->subDays(2),
        ]);

        $resposta = $this->actingAs($this->lojista)->get(route('portal.clientes.show', $this->maria));

        $resposta->assertOk()
            ->assertSee('Bloqueadas')
            ->assertSee('Sem consentimento de marketing válido')
            ->assertSee('Nenhuma data alimenta campanha para este cliente')
            ->assertSee('Revogado');
    }

    public function test_sem_registro_de_consentimento_a_campanha_nasce_bloqueada(): void
    {
        // Ausência de registro não é permissão: o padrão é não enviar.
        $this->actingAs($this->lojista)
            ->get(route('portal.clientes.show', $this->maria))
            ->assertOk()
            ->assertSee('Bloqueadas')
            ->assertSee('Sem registro');
    }

    public function test_concedido_com_revogacao_no_mesmo_registro_conta_como_revogado(): void
    {
        // `granted = true` com `revoked_at` preenchido é consentimento retirado.
        // Ler só o booleano liberaria o envio.
        CustomerConsent::factory()->marketing()->create([
            'customer_id' => $this->maria->getKey(),
            'granted' => true,
            'granted_at' => Carbon::now()->subMonths(3),
            'revoked_at' => Carbon::now()->subDay(),
        ]);

        $this->actingAs($this->lojista)
            ->get(route('portal.clientes.show', $this->maria))
            ->assertOk()
            ->assertSee('Bloqueadas');
    }

    // ─────────────────────────── isolamento ───────────────────────────

    public function test_a_carteira_nao_mostra_cliente_de_outro_lojista(): void
    {
        $this->actingAs($this->lojista)
            ->get(route('portal.clientes.index'))
            ->assertOk()
            ->assertSee('Maria Silva')
            ->assertDontSee('Beatriz Nogueira')
            ->assertDontSee('208.417.665-32');
    }

    public function test_a_busca_nao_alcanca_o_cliente_de_outro_lojista_nem_pelo_cpf(): void
    {
        // O CPF é o campo em que a diferença importa: confirmar que um documento
        // existe na base já é informação sobre o cliente do concorrente.
        $this->actingAs($this->lojista)
            ->get(route('portal.clientes.index', ['q' => '208.417.665-32']))
            ->assertOk()
            ->assertDontSee('Beatriz Nogueira');
    }

    public function test_cliente_de_outro_lojista_devolve_404(): void
    {
        $this->actingAs($this->lojista)
            ->get(route('portal.clientes.show', $this->doVizinho))
            ->assertNotFound();
    }

    public function test_o_cliente_de_outro_lojista_e_o_inexistente_respondem_igual(): void
    {
        // 403 diria "existe, mas não é seu" — e é esse o vazamento.
        $alheio = $this->actingAs($this->lojista)->get(route('portal.clientes.show', $this->doVizinho));
        $inexistente = $this->actingAs($this->lojista)->get(route('portal.clientes.show', 999999));

        $this->assertSame(404, $alheio->status());
        $this->assertSame($inexistente->status(), $alheio->status());
        $this->assertSame($inexistente->getContent(), $alheio->getContent());
    }

    public function test_quem_nao_e_revendedor_aprovado_nao_abre_a_carteira(): void
    {
        // 403 é a resposta certa um degrau antes do escopo: a negativa é sobre o
        // ambiente inteiro, não sobre a existência de um registro.
        $pendente = User::factory()->forReseller(Reseller::factory()->pending()->create())->create();

        $this->actingAs($pendente)->get(route('portal.clientes.index'))->assertForbidden();
    }

    public function test_visitante_sem_sessao_vai_para_o_login(): void
    {
        $this->get(route('portal.clientes.index'))->assertRedirect(route('login'));
    }

    // ─────────────────────────── apoio ───────────────────────────

    /**
     * @param  array<string, mixed>  $atributos
     */
    private function cliente(Reseller $revendedor, array $atributos): Customer
    {
        return Customer::factory()->forReseller($revendedor)->create($atributos + ['user_id' => null]);
    }

    /**
     * @param  array<string, mixed>  $atributos
     */
    private function pedido(Reseller $revendedor, Customer $cliente, array $atributos = []): Order
    {
        return Order::factory()->forReseller($revendedor)->create($atributos + [
            'customer_id' => $cliente->getKey(),
            'user_id' => null,
        ]);
    }

    /**
     * Lê o número do cartão de KPI pelo rótulo, para a asserção falar do valor e
     * não da posição do bloco no HTML.
     */
    private function kpi(string $html, string $rotulo): int
    {
        $encontrou = preg_match(
            '/'.preg_quote($rotulo, '/').'<\/div>\s*<div class="kpi__value">([^<]*)</',
            $html,
            $partes,
        );

        $this->assertSame(1, $encontrou, "KPI \"{$rotulo}\" não encontrado na tela.");

        return (int) trim($partes[1]);
    }
}
