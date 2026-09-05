<?php

/*
[Modulo: tests/Feature/Portal]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Cobre a tela 2.8: fila escopada, abertura de chamado, isolamento por code e a nota interna que nunca vaza.
*/

namespace Tests\Feature\Portal;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Reseller;
use App\Models\Setting;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SuporteTest extends TestCase
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
        $this->lojista = User::factory()->forReseller($this->tomazelli)->create(['name' => 'João Ferreira']);

        $this->semearCanais();
    }

    private function semearCanais(): void
    {
        foreach ([
            'telefone' => '+55 (16) 99487-7800',
            'whatsapp' => '+55 (16) 98888-1200',
            'email' => 'suporte@velaro.com.br',
            'horario' => 'Segunda a sexta, das 8h às 18h',
        ] as $chave => $valor) {
            Setting::factory()->create([
                'group' => 'contact',
                'key' => 'contact.'.$chave,
                'value' => $valor,
                'is_public' => true,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $troca
     * @return array<string, mixed>
     */
    private function formulario(array $troca = []): array
    {
        return array_merge([
            'subject' => 'Dúvida sobre prazos de entrega',
            'category' => SupportTicket::CATEGORY_ORDERS,
            'priority' => SupportTicket::PRIORITY_MEDIUM,
            'order_id' => null,
            'customer_id' => null,
            'body' => 'Gostaria de saber o prazo de entrega do pedido, a cliente casa em agosto.',
        ], $troca);
    }

    private function chamadoDe(Reseller $dono, string $assunto, string $codigo): SupportTicket
    {
        return SupportTicket::factory()->create([
            'reseller_id' => $dono->getKey(),
            'code' => $codigo,
            'subject' => $assunto,
            'category' => SupportTicket::CATEGORY_ORDERS,
            'status' => SupportTicket::STATUS_IN_PROGRESS,
            'priority' => SupportTicket::PRIORITY_MEDIUM,
        ]);
    }

    public function test_a_fila_mostra_so_os_chamados_do_proprio_lojista(): void
    {
        $this->chamadoDe($this->tomazelli, 'Dúvida sobre prazos de entrega', 'SUP-2026-0821');
        $this->chamadoDe($this->vizinho, 'Problema secreto do concorrente', 'SUP-2026-0900');

        $resposta = $this->actingAs($this->lojista)->get(route('portal.suporte.index'));

        $resposta->assertOk();
        $resposta->assertSee('Meus chamados');
        $resposta->assertSee('SUP-2026-0821');
        $resposta->assertSee('Dúvida sobre prazos de entrega');
        $resposta->assertDontSee('SUP-2026-0900');
        $resposta->assertDontSee('Problema secreto do concorrente');
    }

    public function test_os_numeros_do_painel_contam_so_a_base_do_lojista(): void
    {
        SupportTicket::factory()->count(2)->create([
            'reseller_id' => $this->tomazelli->getKey(),
            'status' => SupportTicket::STATUS_IN_PROGRESS,
        ]);
        SupportTicket::factory()->create([
            'reseller_id' => $this->tomazelli->getKey(),
            'status' => SupportTicket::STATUS_AWAITING_CUSTOMER,
        ]);
        SupportTicket::factory()->count(5)->create([
            'reseller_id' => $this->vizinho->getKey(),
            'status' => SupportTicket::STATUS_IN_PROGRESS,
        ]);

        $resposta = $this->actingAs($this->lojista)->get(route('portal.suporte.index'));

        $resposta->assertOk();
        // 3 chamados, não 8: o painel "Status do suporte" é da loja, não da base.
        $resposta->assertSeeInOrder(['<strong>3</strong>', 'Total de chamados'], false);
        $resposta->assertSeeInOrder(['<strong>2</strong>', 'Em atendimento'], false);
    }

    public function test_os_filtros_estreitam_a_fila(): void
    {
        $this->chamadoDe($this->tomazelli, 'Dúvida sobre prazos de entrega', 'SUP-2026-0821');

        SupportTicket::factory()->create([
            'reseller_id' => $this->tomazelli->getKey(),
            'code' => 'SUP-2026-0820',
            'subject' => 'Alteração de endereço de cobrança',
            'category' => SupportTicket::CATEGORY_FINANCE,
            'status' => SupportTicket::STATUS_AWAITING_CUSTOMER,
        ]);

        $porCategoria = $this->actingAs($this->lojista)
            ->get(route('portal.suporte.index', ['categoria' => SupportTicket::CATEGORY_FINANCE]));

        $porCategoria->assertOk();
        $porCategoria->assertSee('SUP-2026-0820');
        $porCategoria->assertDontSee('SUP-2026-0821');

        $porStatus = $this->actingAs($this->lojista)
            ->get(route('portal.suporte.index', ['status' => SupportTicket::STATUS_IN_PROGRESS]));

        $porStatus->assertOk();
        $porStatus->assertSee('SUP-2026-0821');
        $porStatus->assertDontSee('SUP-2026-0820');
    }

    public function test_a_busca_alcanca_o_texto_da_conversa_visivel(): void
    {
        $chamado = $this->chamadoDe($this->tomazelli, 'Assunto qualquer', 'SUP-2026-0821');

        SupportMessage::factory()->create([
            'ticket_id' => $chamado->getKey(),
            'author_id' => $this->lojista->getKey(),
            'body' => 'A cliente perguntou sobre a gravação interna.',
            'is_internal_note' => false,
        ]);

        $resposta = $this->actingAs($this->lojista)->get(route('portal.suporte.index', ['q' => 'gravação interna']));

        $resposta->assertOk();
        $resposta->assertSee('SUP-2026-0821');
    }

    public function test_a_busca_nao_encontra_chamado_pelo_texto_de_uma_nota_interna(): void
    {
        // Achar o chamado por um trecho de nota interna já é o vazamento: a busca
        // confirmaria o conteúdo do que a Velaro escreveu para si mesma.
        $chamado = $this->chamadoDe($this->tomazelli, 'Assunto neutro', 'SUP-2026-0821');

        SupportMessage::factory()->internalNote()->create([
            'ticket_id' => $chamado->getKey(),
            'body' => 'Conferir com a produção antes de prometer prazo ao lojista.',
        ]);

        $resposta = $this->actingAs($this->lojista)
            ->get(route('portal.suporte.index', ['q' => 'antes de prometer prazo']));

        $resposta->assertOk();
        $resposta->assertDontSee('SUP-2026-0821');
        $resposta->assertSee('Nenhum chamado');
    }

    public function test_a_thread_nunca_mostra_a_observacao_interna_da_velaro(): void
    {
        // A regra mais sensível da tela 2.8: `is_internal_note` não pode chegar ao
        // revendedor. O corte é no SQL, não num `@if` da view.
        $chamado = $this->chamadoDe($this->tomazelli, 'Dúvida sobre prazos de entrega', 'SUP-2026-0821');

        SupportMessage::factory()->create([
            'ticket_id' => $chamado->getKey(),
            'author_id' => $this->lojista->getKey(),
            'body' => 'Boa tarde! Qual é o prazo do pedido?',
            'is_internal_note' => false,
        ]);

        SupportMessage::factory()->fromVelaro()->create([
            'ticket_id' => $chamado->getKey(),
            'body' => 'Olá! O pedido entra em produção assim que o lote for quitado.',
            'is_internal_note' => false,
        ]);

        SupportMessage::factory()->internalNote()->create([
            'ticket_id' => $chamado->getKey(),
            'body' => 'ANOTACAO INTERNA: margem apertada com este lojista, segurar desconto.',
        ]);

        $resposta = $this->actingAs($this->lojista)->get(route('portal.suporte.show', $chamado));

        $resposta->assertOk();
        $resposta->assertSee('Boa tarde! Qual é o prazo do pedido?', false);
        $resposta->assertSee('assim que o lote for quitado', false);
        $resposta->assertDontSee('ANOTACAO INTERNA');
        $resposta->assertDontSee('segurar desconto');
    }

    public function test_o_chamado_de_outro_lojista_devolve_404_pelo_code(): void
    {
        // O `code` é sequencial por ano: com 403 o lojista percorreria a faixa e
        // mediria a fila de atendimento do concorrente.
        $doVizinho = $this->chamadoDe($this->vizinho, 'Problema do concorrente', 'SUP-2026-0900');

        $resposta = $this->actingAs($this->lojista)->get(route('portal.suporte.show', $doVizinho));

        $resposta->assertNotFound();
    }

    public function test_chamado_de_outro_lojista_e_inexistente_respondem_igual(): void
    {
        $doVizinho = $this->chamadoDe($this->vizinho, 'Problema do concorrente', 'SUP-2026-0900');

        $alheio = $this->actingAs($this->lojista)->get(route('portal.suporte.show', $doVizinho->code));
        $inexistente = $this->actingAs($this->lojista)->get(route('portal.suporte.show', 'SUP-2026-9999'));

        $this->assertSame(404, $alheio->status());
        $this->assertSame($inexistente->status(), $alheio->status());
        $this->assertSame($inexistente->getContent(), $alheio->getContent());
    }

    public function test_a_abertura_grava_chamado_mensagem_e_marco_de_status(): void
    {
        $resposta = $this->actingAs($this->lojista)
            ->post(route('portal.suporte.store'), $this->formulario());

        $chamado = SupportTicket::query()->firstOrFail();

        $resposta->assertRedirect(route('portal.suporte.show', $chamado));
        $resposta->assertSessionHas('status');

        $this->assertSame($this->tomazelli->getKey(), $chamado->reseller_id);
        $this->assertSame(SupportTicket::STATUS_OPEN, $chamado->status);
        $this->assertSame(SupportTicket::CHANNEL_PORTAL, $chamado->channel);
        $this->assertMatchesRegularExpression('/^SUP-\d{4}-\d{4}$/', (string) $chamado->code);

        $mensagem = $chamado->messages()->firstOrFail();
        $this->assertSame(SupportMessage::AUTHOR_ROLE_RESELLER, $mensagem->author_role);
        $this->assertSame($this->lojista->getKey(), $mensagem->author_id);
        // O portal não tem como criar nota interna: a nota é da Velaro.
        $this->assertFalse($mensagem->is_internal_note);

        $this->assertSame(SupportTicket::STATUS_OPEN, $chamado->statusEvents()->firstOrFail()->to_status);
    }

    public function test_o_protocolo_e_sequencial_e_nao_colide(): void
    {
        $this->actingAs($this->lojista)->post(route('portal.suporte.store'), $this->formulario());
        $this->actingAs($this->lojista)->post(route('portal.suporte.store'), $this->formulario([
            'subject' => 'Segundo chamado',
        ]));

        $codigos = SupportTicket::query()->orderBy('id')->pluck('code')->all();

        $this->assertCount(2, $codigos);
        $this->assertNotSame($codigos[0], $codigos[1]);
    }

    public function test_campos_obrigatorios_e_enums_fora_da_lista_reprovam(): void
    {
        $resposta = $this->actingAs($this->lojista)->post(route('portal.suporte.store'), $this->formulario([
            'subject' => '',
            'category' => 'Categoria inventada',
            'priority' => 'urgentissima',
            'body' => 'curto',
        ]));

        $resposta->assertSessionHasErrors(['subject', 'category', 'priority', 'body']);
        $this->assertDatabaseCount('support_tickets', 0);
    }

    public function test_o_pedido_de_outro_lojista_nao_pode_ser_vinculado(): void
    {
        $doVizinho = Order::factory()->forReseller($this->vizinho)->create(['user_id' => null]);

        $resposta = $this->actingAs($this->lojista)->post(route('portal.suporte.store'), $this->formulario([
            'order_id' => $doVizinho->getKey(),
        ]));

        // Mesma mensagem de um id inexistente: a recusa não pode confirmar que o
        // pedido existe na base de outro lojista.
        $resposta->assertSessionHasErrors('order_id');
        $this->assertDatabaseCount('support_tickets', 0);
    }

    public function test_o_cliente_de_outro_lojista_nao_pode_ser_vinculado(): void
    {
        $doVizinho = Customer::factory()->forReseller($this->vizinho)->create(['user_id' => null]);

        $resposta = $this->actingAs($this->lojista)->post(route('portal.suporte.store'), $this->formulario([
            'customer_id' => $doVizinho->getKey(),
        ]));

        $resposta->assertSessionHasErrors('customer_id');
        $this->assertDatabaseCount('support_tickets', 0);
    }

    public function test_o_pedido_e_o_cliente_do_proprio_lojista_sao_vinculados(): void
    {
        $cliente = Customer::factory()->forReseller($this->tomazelli)->create([
            'user_id' => null,
            'name' => 'Maria Silva',
        ]);
        $pedido = Order::factory()->forReseller($this->tomazelli)->create([
            'user_id' => null,
            'customer_id' => $cliente->getKey(),
        ]);

        $this->actingAs($this->lojista)->post(route('portal.suporte.store'), $this->formulario([
            'order_id' => $pedido->getKey(),
            'customer_id' => $cliente->getKey(),
        ]));

        $chamado = SupportTicket::query()->firstOrFail();

        $this->assertSame($pedido->getKey(), $chamado->order_id);
        $this->assertSame($cliente->getKey(), $chamado->customer_id);

        // O consumidor final aparece como pessoa vinculada, e não como parte da
        // conversa.
        $resposta = $this->actingAs($this->lojista)->get(route('portal.suporte.show', $chamado));
        $resposta->assertOk();
        $resposta->assertSee('Maria Silva');
        $resposta->assertSee('Vinculada ao pedido');
    }

    public function test_a_tela_de_abertura_so_oferece_pedidos_e_clientes_do_proprio_lojista(): void
    {
        Customer::factory()->forReseller($this->tomazelli)->create(['user_id' => null, 'name' => 'Maria Silva']);
        Customer::factory()->forReseller($this->vizinho)->create(['user_id' => null, 'name' => 'Cliente Do Vizinho']);

        $resposta = $this->actingAs($this->lojista)->get(route('portal.suporte.create'));

        $resposta->assertOk();
        $resposta->assertSee('Abrir novo chamado');
        $resposta->assertSee('Maria Silva');
        $resposta->assertDontSee('Cliente Do Vizinho');
    }

    public function test_os_anexos_da_abertura_ficam_no_disco_privado(): void
    {
        Storage::fake('local');

        $this->actingAs($this->lojista)->post(route('portal.suporte.store'), $this->formulario([
            'anexos' => [UploadedFile::fake()->create('espelho_pedido.pdf', 120, 'application/pdf')],
        ]));

        $chamado = SupportTicket::query()->firstOrFail();
        $anexo = $chamado->attachments()->firstOrFail();

        $this->assertSame('espelho_pedido.pdf', $anexo->original_name);
        $this->assertSame('local', $anexo->disk);
        Storage::disk('local')->assertExists((string) $anexo->path);

        // O anexo pode carregar nota fiscal e nome de consumidor final: nunca no
        // disco público.
        Storage::disk('public')->assertMissing((string) $anexo->path);
    }

    public function test_anexo_de_tipo_proibido_e_recusado(): void
    {
        Storage::fake('local');

        $resposta = $this->actingAs($this->lojista)->post(route('portal.suporte.store'), $this->formulario([
            'anexos' => [UploadedFile::fake()->create('planilha.xlsx', 40)],
        ]));

        $resposta->assertSessionHasErrors('anexos.0');
        $this->assertDatabaseCount('support_tickets', 0);
    }

    public function test_a_tela_do_chamado_mostra_a_ficha_e_os_canais(): void
    {
        $chamado = $this->chamadoDe($this->tomazelli, 'Dúvida sobre prazos de entrega', 'SUP-2026-0821');

        $resposta = $this->actingAs($this->lojista)->get(route('portal.suporte.show', $chamado));

        $resposta->assertOk();
        $resposta->assertSee('SUP-2026-0821');
        $resposta->assertSee('Em atendimento');
        $resposta->assertSee('Tomazelli Alianças', false);
        $resposta->assertSee('suporte@velaro.com.br');
        $resposta->assertSee('+55 (16) 98888-1200');
        $resposta->assertSee('Observações internas', false);
    }

    public function test_quem_nao_e_lojista_aprovado_nao_entra_no_suporte(): void
    {
        $pendente = User::factory()->forReseller(Reseller::factory()->pending()->create())->create();

        $this->actingAs($pendente)->get(route('portal.suporte.index'))->assertForbidden();
        $this->actingAs($pendente)->get(route('portal.suporte.create'))->assertForbidden();
    }
}
