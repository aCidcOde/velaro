<?php

/*
[Modulo: tests/Feature/Portal]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Cobre a tela 2.11: o bloco de retirada do detalhe, a comunicacao em nome da loja e o log de notificacoes.
*/

namespace Tests\Feature\Portal;

use App\Models\Customer;
use App\Models\NotificationLog;
use App\Models\Order;
use App\Models\Reseller;
use App\Models\ResellerStore;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Tela 2.11 — pedido pronto para retirada.
 *
 * O protótipo mostra o celular do consumidor, não uma tela de sistema: a
 * implicação declarada no escopo é que o Portal precisa do **painel de disparo e
 * histórico** — gatilho na chegada, prévia da mensagem, canais, log de envio e
 * confirmação de retirada. É um estado do detalhe do pedido, não uma rota nova.
 */
class RetiradaTest extends TestCase
{
    use RefreshDatabase;

    private Reseller $tomazelli;

    private Reseller $vizinho;

    private User $lojista;

    private Customer $maria;

    private ResellerStore $loja;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tomazelli = Reseller::factory()->approved()->create(['trade_name' => 'Tomazelli Alianças']);
        $this->vizinho = Reseller::factory()->approved()->create(['trade_name' => 'Aliança & Cia']);
        $this->lojista = User::factory()->forReseller($this->tomazelli)->create();

        $this->loja = ResellerStore::factory()->published()->create([
            'reseller_id' => $this->tomazelli->getKey(),
            'name' => 'Tomazelli Alianças',
            'address' => 'Rua das Alianças, 123 - Centro',
        ]);

        $this->maria = Customer::factory()->forReseller($this->tomazelli)->create([
            'name' => 'Maria Silva',
            'phone' => '(11) 98765-4321',
            'email' => 'maria.silva@email.com',
            'user_id' => null,
        ]);
    }

    // ─────────────────────────── o estado ───────────────────────────

    public function test_pedido_pronto_para_retirada_abre_o_bloco_de_retirada(): void
    {
        $pedido = $this->pedidoPronto();

        $this->actingAs($this->lojista)
            ->get(route('portal.pedidos.show', $pedido))
            ->assertOk()
            ->assertSee('Notificações enviadas')
            ->assertSee('Confirmação de retirada')
            ->assertSee('Como o cliente recebe')
            ->assertSee('Pronto para retirada');
    }

    public function test_pedido_que_ainda_nao_chegou_nao_mostra_o_bloco(): void
    {
        // O painel de disparo é do estado "chegou na loja": mostrá-lo antes
        // ofereceria confirmar a retirada de um pedido que ainda está na fábrica.
        $emProducao = $this->pedido([
            'operational_status' => Order::OPERATIONAL_STATUS_IN_PRODUCTION,
            'payment_status' => Order::PAYMENT_STATUS_PAID,
        ]);

        $this->actingAs($this->lojista)
            ->get(route('portal.pedidos.show', $emProducao))
            ->assertOk()
            ->assertDontSee('Confirmação de retirada')
            ->assertDontSee('Como o cliente recebe');
    }

    public function test_o_bloco_continua_depois_de_retirado_como_comprovante(): void
    {
        $pedido = $this->pedido([
            'operational_status' => Order::OPERATIONAL_STATUS_PICKED_UP,
            'payment_status' => Order::PAYMENT_STATUS_PAID,
            'arrived_at' => Carbon::parse('2026-05-19 08:15:00'),
            'picked_up_at' => Carbon::parse('2026-05-23 15:40:00'),
            'picked_up_by_name' => 'João Silva',
            'picked_up_by_document' => '987.654.321-00',
        ]);

        $this->actingAs($this->lojista)
            ->get(route('portal.pedidos.show', $pedido))
            ->assertOk()
            ->assertSee('Retirada confirmada')
            ->assertSee('João Silva')
            ->assertSee('987.654.321-00')
            ->assertSee('23/05/2026 15:40');
    }

    // ─────────────────────────── comunicação ───────────────────────────

    public function test_a_previa_sai_em_nome_da_loja_e_sem_a_marca_velaro(): void
    {
        // Regra 1 da tela: a comunicação é disparada EM NOME DO REVENDEDOR. A
        // marca da fábrica não aparece para o consumidor final (Anexo I §4.12).
        $pedido = $this->pedidoPronto();

        $resposta = $this->actingAs($this->lojista)->get(route('portal.pedidos.show', $pedido));

        $resposta->assertOk()
            ->assertSee('Olá, Maria Silva! Seu pedido #'.$pedido->public_number.' já chegou à loja e está pronto para retirada.')
            ->assertSee('📍 Endereço: Rua das Alianças, 123 - Centro')
            ->assertSee('🕐 Horário: seg. a sex., das 9h às 18h.')
            ->assertSee('✓ Estamos te esperando!')
            ->assertSee('Seu pedido está pronto para retirada')
            ->assertSee('já está disponível para retirada na loja Tomazelli Alianças');

        // A prévia é o texto que o consumidor lê: a marca da fábrica não entra nele.
        $previa = $this->trechoDaPrevia((string) $resposta->getContent());
        $this->assertStringNotContainsStringIgnoringCase('velaro', $previa);
    }

    public function test_a_previa_mostra_os_dois_canais_com_o_destino_do_cliente(): void
    {
        $pedido = $this->pedidoPronto();

        $this->actingAs($this->lojista)
            ->get(route('portal.pedidos.show', $pedido))
            ->assertOk()
            ->assertSee('WhatsApp')
            ->assertSee('(11) 98765-4321')
            ->assertSee('maria.silva@email.com');
    }

    public function test_o_log_de_envio_lista_canal_destinatario_e_situacao(): void
    {
        $pedido = $this->pedidoPronto();

        NotificationLog::factory()->viaWhatsapp()->forOrder($pedido)->forCustomer($this->maria)->create([
            'reseller_id' => $this->tomazelli->getKey(),
            'recipient' => '(11) 98765-4321',
            'status' => 'sent',
            'sent_at' => Carbon::parse('2026-05-19 08:15:00'),
        ]);
        NotificationLog::factory()->viaEmail()->forOrder($pedido)->forCustomer($this->maria)->create([
            'reseller_id' => $this->tomazelli->getKey(),
            'recipient' => 'maria.silva@email.com',
            'status' => 'failed',
            'error_message' => 'Caixa de entrada cheia.',
        ]);

        $this->actingAs($this->lojista)
            ->get(route('portal.pedidos.show', $pedido))
            ->assertOk()
            ->assertSee('Cliente · WhatsApp')
            ->assertSee('enviado em 19/05/2026 08:15')
            ->assertSee('Enviado')
            ->assertSee('Falhou')
            ->assertSee('Caixa de entrada cheia.');
    }

    public function test_sem_envio_registrado_a_tela_diz_que_nao_houve_disparo(): void
    {
        $pedido = $this->pedidoPronto();

        $this->actingAs($this->lojista)
            ->get(route('portal.pedidos.show', $pedido))
            ->assertOk()
            ->assertSee('Nenhum envio registrado ainda');
    }

    public function test_o_aviso_de_retirada_e_transacional_e_nao_depende_de_marketing(): void
    {
        // Regra 3 da tela 2.3 e §6: transacional e promocional são tratados
        // separadamente. O cliente sem consentimento de marketing continua sendo
        // avisado de que o pedido dele chegou.
        $pedido = $this->pedidoPronto();

        $this->assertSame(0, $this->maria->consents()->count());

        $this->actingAs($this->lojista)
            ->get(route('portal.pedidos.show', $pedido))
            ->assertOk()
            ->assertSee('Comunicação transacional')
            ->assertSee('Olá, Maria Silva!');
    }

    // ─────────────────────────── isolamento ───────────────────────────

    public function test_o_log_de_outro_lojista_nao_entra_no_painel(): void
    {
        $pedido = $this->pedidoPronto();

        // Log apontando para o pedido certo, mas gravado sob outro revendedor:
        // `notification_logs` guarda telefone e e-mail de consumidor final e é a
        // última tabela em que valeria a pena economizar a cláusula de dono.
        NotificationLog::factory()->viaWhatsapp()->forOrder($pedido)->create([
            'reseller_id' => $this->vizinho->getKey(),
            'recipient' => '(41) 99655-2130',
            'status' => 'sent',
        ]);

        $this->actingAs($this->lojista)
            ->get(route('portal.pedidos.show', $pedido))
            ->assertOk()
            ->assertDontSee('(41) 99655-2130')
            ->assertSee('Nenhum envio registrado ainda');
    }

    public function test_pedido_pronto_de_outro_lojista_devolve_404(): void
    {
        $doVizinho = Order::factory()->forReseller($this->vizinho)->readyForPickup()->create([
            'customer_id' => Customer::factory()->forReseller($this->vizinho)->create(['user_id' => null])->getKey(),
            'user_id' => null,
        ]);

        $this->actingAs($this->lojista)
            ->get(route('portal.pedidos.show', $doVizinho))
            ->assertNotFound();
    }

    // ─────────────────────────── apoio ───────────────────────────

    /**
     * @param  array<string, mixed>  $atributos
     */
    private function pedido(array $atributos): Order
    {
        return Order::factory()->forReseller($this->tomazelli)->create($atributos + [
            'customer_id' => $this->maria->getKey(),
            'user_id' => null,
        ]);
    }

    private function pedidoPronto(): Order
    {
        return $this->pedido([
            'public_number' => 'ORD012549',
            'operational_status' => Order::OPERATIONAL_STATUS_READY_FOR_PICKUP,
            'payment_status' => Order::PAYMENT_STATUS_PAID,
            'arrived_at' => Carbon::parse('2026-05-19 08:15:00'),
        ]);
    }

    /**
     * Só o miolo do celular do protótipo — é ali que está o texto que o
     * consumidor lê, e é ali que a marca da fábrica não pode aparecer.
     */
    private function trechoDaPrevia(string $html): string
    {
        $encontrou = preg_match('/<div class="phone">(.*?)<\/div>\s*<\/div>\s*<\/div>/s', $html, $partes);

        $this->assertSame(1, $encontrou, 'A prévia da mensagem não foi encontrada na tela.');

        return $partes[1];
    }
}
