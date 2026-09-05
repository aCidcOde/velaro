<?php

/*
[Modulo: tests/Feature/Portal]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Prova que o Portal do Lojista nao vaza dado entre revendedores e que o registro alheio some com 404.
*/

namespace Tests\Feature\Portal;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderBatch;
use App\Models\Reseller;
use App\Models\ResellerPriceRule;
use App\Models\ResellerStore;
use App\Models\SupportTicket;
use App\Models\User;
use App\Support\ResellerScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * A regra central do ambiente: tudo é escopado por `reseller_id`.
 *
 * Cada caso monta dois lojistas com a mesma forma de dado — mudando só o dono — e
 * confere que o segundo nunca aparece para o primeiro. É por isso que o seed tem
 * um segundo revendedor: sem vizinho na base, "não vejo o dado do outro" é uma
 * afirmação que nenhum teste consegue derrubar.
 */
class ResellerScopeTest extends TestCase
{
    use RefreshDatabase;

    private Reseller $tomazelli;

    private Reseller $vizinho;

    private User $lojista;

    /** @var array<string, Order|Customer|OrderBatch|SupportTicket|ResellerPriceRule|ResellerStore> */
    private array $doVizinho = [];

    /** @var array<string, Order|Customer|OrderBatch|SupportTicket|ResellerPriceRule|ResellerStore> */
    private array $meus = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->tomazelli = Reseller::factory()->approved()->create();
        $this->vizinho = Reseller::factory()->approved()->create();

        $this->lojista = User::factory()->forReseller($this->tomazelli)->create();

        $this->meus = $this->semearLojista($this->tomazelli);
        $this->doVizinho = $this->semearLojista($this->vizinho);
    }

    /**
     * Uma linha de cada model coberto, para o mesmo dono.
     *
     * @return array<string, Order|Customer|OrderBatch|SupportTicket|ResellerPriceRule|ResellerStore>
     */
    private function semearLojista(Reseller $revendedor): array
    {
        $cliente = Customer::factory()->forReseller($revendedor)->create(['user_id' => null]);
        $lote = OrderBatch::factory()->open()->create(['reseller_id' => $revendedor->getKey()]);

        return [
            'customer' => $cliente,
            'batch' => $lote,
            'order' => Order::factory()->forReseller($revendedor)->create([
                'customer_id' => $cliente->getKey(),
                'batch_id' => $lote->getKey(),
                'user_id' => null,
            ]),
            'ticket' => SupportTicket::factory()->create(['reseller_id' => $revendedor->getKey()]),
            'rule' => ResellerPriceRule::factory()->create(['reseller_id' => $revendedor->getKey()]),
            'store' => ResellerStore::factory()->published()->create(['reseller_id' => $revendedor->getKey()]),
        ];
    }

    public function test_escopo_lista_apenas_os_registros_do_proprio_lojista(): void
    {
        $escopo = ResellerScope::for($this->tomazelli);

        $consultas = [
            'order' => $escopo->orders(),
            'customer' => $escopo->customers(),
            'batch' => $escopo->batches(),
            'ticket' => $escopo->tickets(),
            'rule' => $escopo->priceRules(),
        ];

        foreach ($consultas as $chave => $consulta) {
            $ids = $consulta->pluck('id')->all();

            $this->assertSame([$this->meus[$chave]->getKey()], $ids, "vazou em {$chave}");
        }

        $this->assertSame($this->meus['store']->getKey(), $escopo->store()?->getKey());
    }

    public function test_scope_owned_by_filtra_todos_os_models_cobertos(): void
    {
        foreach (ResellerScope::SCOPED_MODELS as $model) {
            $doDono = $model::query()->ownedBy($this->tomazelli)->pluck('reseller_id')->all();

            $this->assertNotEmpty($doDono, "{$model} nao devolveu nada para o dono");
            $this->assertSame(
                [$this->tomazelli->getKey()],
                array_values(array_unique($doDono)),
                "{$model} trouxe linha de outro revendedor"
            );
        }
    }

    public function test_escopo_sem_revendedor_nao_devolve_a_tabela_inteira(): void
    {
        // Se a origem do escopo se perdeu no caminho, o resultado seguro é vazio —
        // nunca a base toda.
        foreach (ResellerScope::SCOPED_MODELS as $model) {
            $this->assertSame(0, $model::query()->ownedBy(null)->count(), "{$model} vazou com dono nulo");
        }
    }

    public function test_registro_orfao_nao_pertence_a_lojista_nenhum(): void
    {
        // `orders.reseller_id` é nulável: o scaffold tem pedido sem lojista. Nulo
        // não pode significar "de todos".
        $orfao = Order::factory()->create(['reseller_id' => null, 'user_id' => null]);

        $this->assertFalse($orfao->isOwnedBy($this->tomazelli));
        $this->assertFalse($orfao->isOwnedBy($this->vizinho));
        $this->assertNull($orfao->resellerOwnerId());
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function rotasEscopadas(): array
    {
        return [
            'pedido' => ['portal.pedidos.show', 'order'],
            'cliente' => ['portal.clientes.show', 'customer'],
            'lote' => ['portal.financeiro.pagamento', 'batch'],
            'chamado' => ['portal.suporte.show', 'ticket'],
        ];
    }

    #[DataProvider('rotasEscopadas')]
    public function test_registro_de_outro_lojista_devolve_404(string $rota, string $chave): void
    {
        $resposta = $this->actingAs($this->lojista)->get(route($rota, $this->doVizinho[$chave]));

        $resposta->assertNotFound();
    }

    #[DataProvider('rotasEscopadas')]
    public function test_registro_do_proprio_lojista_atravessa_o_binding(string $rota, string $chave): void
    {
        // As telas ainda não existem (os controllers respondem 501), então o que se
        // afirma aqui é o que continua verdadeiro depois delas: o registro do dono
        // NÃO é barrado pelo escopo.
        $resposta = $this->actingAs($this->lojista)->get(route($rota, $this->meus[$chave]));

        $this->assertNotSame(404, $resposta->status(), "o escopo barrou o registro do proprio dono em {$rota}");
    }

    public function test_o_de_outro_lojista_e_o_inexistente_respondem_exatamente_igual(): void
    {
        // 403 diria "existe, mas não é seu" — e é justamente esse o vazamento: os
        // identificadores são curtos e sequenciais, e a diferença de status deixaria
        // medir a base do concorrente. As duas respostas têm de ser indistinguíveis.
        $doVizinho = $this->respostaDoPortal('portal.pedidos.show', $this->doVizinho['order']->getRouteKey());
        $inexistente = $this->respostaDoPortal('portal.pedidos.show', 'ORD999999');

        $this->assertSame(404, $doVizinho->status());
        $this->assertSame($inexistente->status(), $doVizinho->status());
        $this->assertSame($inexistente->getContent(), $doVizinho->getContent());
    }

    public function test_chamado_de_outro_lojista_nao_e_alcancavel_pelo_codigo(): void
    {
        // O `code` do chamado é sequencial por ano (SUP-2026-0598): sem o escopo,
        // percorrer a faixa devolveria a fila de atendimento da base inteira.
        $resposta = $this->respostaDoPortal('portal.suporte.show', (string) $this->doVizinho['ticket']->code);

        $resposta->assertNotFound();
    }

    public function test_lojista_bloqueado_e_nao_aprovado_nao_entram_no_negocio_do_portal(): void
    {
        // Bloqueado perde a sessão e volta para o login (é o `not_blocked`, um
        // degrau antes do escopo) — inclusive no painel, que é a única rota do
        // portal aberta a lojista não aprovado.
        $bloqueado = User::factory()->blocked()->forReseller($this->tomazelli)->create();

        $this->actingAs($bloqueado)
            ->get(route('portal.dashboard'))
            ->assertRedirect(route('login'));

        // Cadastro ainda em análise: aqui o 403 é a resposta certa, porque a
        // negativa é sobre o ambiente inteiro e não sobre a existência de um
        // registro.
        $pendente = Reseller::factory()->pending()->create();
        $semAprovacao = User::factory()->forReseller($pendente)->create();

        $this->actingAs($semAprovacao)->get(route('portal.catalogo'))->assertForbidden();
        $this->actingAs($semAprovacao)->get(route('portal.pedidos.index'))->assertForbidden();

        // O painel é a exceção nominal: ele abre e mostra o estágio da jornada,
        // sem número de negócio nenhum. É por isso que o escopo continua intacto
        // — a exceção vale para uma rota, e ela não consulta pedido, cliente,
        // financeiro nem preço.
        $this->actingAs($semAprovacao)->get(route('portal.dashboard'))->assertOk();
    }

    public function test_binding_fora_do_portal_continua_o_do_laravel(): void
    {
        // O bind é global por nome de parâmetro, e `{order}` e `{customer}` também
        // existem no Master, na vitrine, no app e na API mobile. Fora do portal o
        // parâmetro volta cru e o binding implícito resolve como sempre resolveu —
        // inclusive o pedido de OUTRO revendedor, que o Master precisa enxergar.
        $admin = $this->createBackendAdmin();

        $resposta = $this->actingAs($admin)->get(route('backend.pedidos.show', $this->doVizinho['order']));

        $this->assertNotSame(404, $resposta->status());
    }

    private function respostaDoPortal(string $rota, string $chave): TestResponse
    {
        return $this->actingAs($this->lojista)->get(route($rota, $chave));
    }
}
