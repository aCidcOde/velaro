<?php

/*
[Modulo: app/Services/Portal]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Monta a carteira de clientes do lojista (tela 2.3): KPIs, lista filtravel e a ficha com relacionamento e LGPD.
*/

namespace App\Services\Portal;

use App\Models\Customer;
use App\Models\CustomerConsent;
use App\Models\Order;
use App\Services\Portal\Concerns\FormataDados;
use App\Support\BrazilianStates;
use App\Support\ResellerContactSources;
use App\Support\ResellerScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

/**
 * O CRM da carteira do lojista — tela 2.3.
 *
 * Toda consulta daqui nasce em {@see ResellerScope::customers()}, isto é, na
 * relação do próprio revendedor autenticado. Não existe `Customer::query()` neste
 * arquivo: o `WHERE reseller_id` é estrutural e não um filtro que dá para
 * esquecer numa refatoração. As subconsultas de pedido repetem o filtro por
 * revendedor de propósito — `orders.reseller_id` é uma coluna própria e o pedido
 * órfão do scaffold não pode entrar na conta de ninguém.
 */
class ClientesService
{
    use FormataDados;

    /** Linhas por página da tabela do protótipo. */
    private const POR_PAGINA = 12;

    /**
     * Um cliente conta como **ativo** quando comprou dentro desta janela.
     *
     * `customers` não tem coluna de situação — e não deveria ter: "ativo" é um
     * fato sobre o histórico de compra, não um campo que alguém marca à mão e
     * esquece de desmarcar. Seis meses é a régua: cobre com folga o ciclo de uma
     * aliança (noivado, casamento, aliança de reposição) sem manter na lista de
     * ativos quem passou na loja uma vez há dois anos.
     */
    private const JANELA_ATIVIDADE_DIAS = 180;

    /**
     * Vocabulário do select "Origem do contato" do cadastro do consumidor final.
     *
     * Não é o mesmo de {@see ResellerContactSources}, que responde
     * como o **lojista** chegou à Velaro; aqui a pergunta é como o consumidor
     * chegou à loja do lojista.
     *
     * @var array<string, string>
     */
    public const ORIGENS_DO_CONTATO = [
        'indicacao' => 'Indicação',
        'vitrine' => 'Vitrine da loja',
        'instagram' => 'Instagram',
        'whatsapp' => 'WhatsApp',
        'loja' => 'Passou na loja',
        'evento' => 'Feira ou evento',
        'outro' => 'Outro',
    ];

    /** Quantos pedidos a ficha lista antes de mandar o lojista para a tela 2.5. */
    private const PEDIDOS_NA_FICHA = 20;

    public function __construct(
        private readonly ResellerScope $escopo,
        private readonly StatusDoPedido $status,
    ) {}

    /**
     * Dados de `GET /portal/clientes`.
     *
     * @param  array{q: string|null, situacao: string|null, cidade: string|null, uf: string|null, local: string|null, periodo: int}  $filtros
     * @return array<string, mixed>
     */
    public function montarIndice(array $filtros): array
    {
        $clientes = $this->listar($filtros);

        return [
            'filtros' => $filtros,
            'kpis' => $this->kpis(),
            'opcoes' => $this->opcoesDeFiltro(),
            'clientes' => $clientes,
            'linhas' => $this->linhas($clientes->getCollection()),
            'temFiltro' => $this->temFiltro($filtros),
            'carteiraVazia' => $this->escopo->customers()->count() === 0,
            'origens' => self::ORIGENS_DO_CONTATO,
            'ufs' => BrazilianStates::all(),
        ];
    }

    /**
     * Dados de `GET /portal/clientes/{customer}`.
     *
     * O cliente já chega verificado: {@see ResellerScope::bindRouteParameters()}
     * devolveu 404 se ele fosse da carteira de outro lojista. Ainda assim as
     * consultas de pedido daqui saem do escopo, e não de `$cliente->orders()`
     * direto — cliente e pedido têm dono próprio no schema, e o histórico da
     * ficha mostra só o que é do lojista que está olhando.
     *
     * @return array<string, mixed>
     */
    public function montarFicha(Customer $cliente): array
    {
        $pedidos = $this->pedidosDoCliente($cliente);
        $consentimentos = $this->consentimentos($cliente);
        $resumo = $this->resumoDeCompra($cliente);

        return [
            'cliente' => $cliente,
            'identidade' => $this->identidade($cliente),
            'cadastro' => $this->cadastro($cliente),
            'relacionamento' => $this->relacionamento($cliente),
            'consentimentos' => $consentimentos,
            'campanhas' => $this->campanhas($cliente, $consentimentos['marketing']['valido']),
            'resumo' => $resumo,
            'pedidos' => $this->linhasDePedido($pedidos),
            'totalDePedidos' => $resumo['pedidos'],
        ];
    }

    /**
     * Os quatro KPIs do topo da tela 2.3.
     *
     * @return list<array{rotulo: string, valor: string, icone: string, tom: string, url: string}>
     */
    private function kpis(): array
    {
        $cadastrados = $this->escopo->customers()->count();
        $ativos = $this->escopo->customers()
            ->whereHas('orders', fn (Builder $consulta): Builder => $this->doRevendedor($consulta)
                ->where('orders.created_at', '>=', $this->inicioDaAtividade()))
            ->count();

        $emAberto = $this->escopo->orders()
            ->whereIn('operational_status', StatusDoPedido::EM_ABERTO)
            ->count();

        /** @var Customer|null $ultimo */
        $ultimo = $this->escopo->customers()->latest('created_at')->latest('id')->first();

        return [
            [
                'rotulo' => 'Clientes cadastrados',
                'valor' => (string) $cadastrados,
                'icone' => 'users',
                'tom' => 'gold',
                'url' => route('portal.clientes.index'),
            ],
            [
                'rotulo' => 'Clientes ativos',
                'valor' => (string) $ativos,
                'icone' => 'check',
                'tom' => 'ok',
                'url' => route('portal.clientes.index', ['situacao' => 'ativo']),
            ],
            [
                'rotulo' => 'Pedidos em aberto',
                'valor' => (string) $emAberto,
                'icone' => 'bag',
                'tom' => 'info',
                'url' => route('portal.pedidos.index', ['periodo' => 0]),
            ],
            [
                'rotulo' => 'Último cadastro',
                'valor' => $this->dataRelativa($ultimo?->created_at),
                'icone' => 'calendar',
                'tom' => 'violet',
                'url' => route('portal.clientes.index', ['periodo' => 30]),
            ],
        ];
    }

    /**
     * A consulta da tabela, já paginada.
     *
     * A data e o número do último pedido entram por subconsulta em vez de
     * `with('orders')`: a coluna "Último pedido" precisa de uma linha por
     * cliente, e carregar a carteira inteira de pedidos para descartar todos
     * menos o primeiro seria pagar a lista completa em memória a cada página.
     *
     * @param  array{q: string|null, situacao: string|null, cidade: string|null, uf: string|null, local: string|null, periodo: int}  $filtros
     * @return LengthAwarePaginator<int, Customer>
     */
    private function listar(array $filtros): LengthAwarePaginator
    {
        $consulta = $this->escopo->customers()
            ->select('customers.*')
            ->addSelect([
                'ultimo_pedido_em' => $this->ultimoPedido('created_at'),
                'ultimo_pedido_numero' => $this->ultimoPedido('public_number'),
            ])
            ->withCount([
                'orders as pedidos_count' => fn (Builder $consulta): Builder => $this->doRevendedor($consulta),
            ]);

        if ($filtros['q'] !== null) {
            $busca = '%'.$filtros['q'].'%';

            $consulta->where(function (Builder $consulta) use ($busca): void {
                $consulta->where('customers.name', 'like', $busca)
                    ->orWhere('customers.document', 'like', $busca)
                    ->orWhere('customers.email', 'like', $busca)
                    ->orWhere('customers.phone', 'like', $busca);
            });
        }

        if ($filtros['cidade'] !== null) {
            $consulta->where('customers.city', $filtros['cidade']);
        }

        if ($filtros['uf'] !== null) {
            $consulta->where('customers.state', $filtros['uf']);
        }

        if ($filtros['periodo'] > 0) {
            $consulta->where('customers.created_at', '>=', Carbon::now()->subDays($filtros['periodo']));
        }

        if ($filtros['situacao'] !== null) {
            $comprouNaJanela = fn (Builder $consulta): Builder => $this->doRevendedor($consulta)
                ->where('orders.created_at', '>=', $this->inicioDaAtividade());

            $filtros['situacao'] === 'ativo'
                ? $consulta->whereHas('orders', $comprouNaJanela)
                : $consulta->whereDoesntHave('orders', $comprouNaJanela);
        }

        return $consulta
            ->orderByDesc('customers.created_at')
            ->orderByDesc('customers.id')
            ->paginate(self::POR_PAGINA)
            ->withQueryString();
    }

    /**
     * Subconsulta do último pedido do cliente, restrita ao revendedor logado.
     *
     * @return Builder<Order>
     */
    private function ultimoPedido(string $coluna): Builder
    {
        return Order::query()
            ->select($coluna)
            ->whereColumn('orders.customer_id', 'customers.id')
            ->where('orders.reseller_id', $this->escopo->reseller->getKey())
            ->orderByDesc('orders.created_at')
            ->orderByDesc('orders.id')
            ->limit(1);
    }

    /**
     * As linhas da tabela, prontas para a view.
     *
     * @param  EloquentCollection<int, Customer>  $clientes
     * @return list<array<string, mixed>>
     */
    private function linhas(EloquentCollection $clientes): array
    {
        $limite = $this->inicioDaAtividade();

        return $clientes->map(function (Customer $cliente) use ($limite): array {
            // A data vem da subconsulta `addSelect`, isto é, crua do driver — sem
            // passar pelo cast do model. `momento()` é quem normaliza os dois casos.
            $momento = $this->momento($cliente->getAttribute('ultimo_pedido_em'));
            $ativo = $momento !== null && $momento->greaterThanOrEqualTo($limite);

            return [
                'id' => $cliente->getKey(),
                'nome' => $this->texto($cliente->name),
                'iniciais' => $this->iniciais($this->texto($cliente->name)),
                'cidadeUf' => $this->cidadeUf($this->textoOuNulo($cliente->getAttribute('city')), $this->textoOuNulo($cliente->getAttribute('state'))),
                'documento' => $this->textoOuNulo($cliente->document),
                'telefone' => $this->textoOuNulo($cliente->phone),
                'email' => $this->textoOuNulo($cliente->email),
                'ultimoPedidoEm' => $this->data($momento),
                'ultimoPedidoNumero' => $this->textoOuNulo($cliente->getAttribute('ultimo_pedido_numero')),
                'pedidos' => (int) $cliente->getAttribute('pedidos_count'),
                'ativo' => $ativo,
                'situacao' => $ativo ? 'Ativo' : 'Inativo',
                'chip' => $ativo ? 'chip--ok' : 'chip--danger',
                'url' => route('portal.clientes.show', $cliente),
            ];
        })->all();
    }

    /**
     * Opções dos três selects da barra de filtros. As cidades saem da própria
     * carteira: oferecer a lista de municípios do país inteiro num select em que
     * o lojista tem oito clientes seria ruído.
     *
     * @return array<string, list<array{valor: string, rotulo: string}>>
     */
    private function opcoesDeFiltro(): array
    {
        $locais = $this->escopo->customers()
            ->select('customers.city', 'customers.state')
            ->whereNotNull('customers.city')
            ->where('customers.city', '!=', '')
            ->distinct()
            ->orderBy('customers.city')
            ->get()
            ->map(function (Customer $cliente): ?array {
                $cidade = $this->textoOuNulo($cliente->getAttribute('city'));
                $uf = $this->textoOuNulo($cliente->getAttribute('state'));

                if ($cidade === null) {
                    return null;
                }

                return [
                    'valor' => $cidade.'|'.(string) $uf,
                    'rotulo' => (string) $this->cidadeUf($cidade, $uf),
                ];
            })
            ->filter()
            ->values()
            ->all();

        return [
            'locais' => array_values($locais),
            'situacoes' => [
                ['valor' => 'ativo', 'rotulo' => 'Ativo'],
                ['valor' => 'inativo', 'rotulo' => 'Inativo'],
            ],
            'periodos' => [
                ['valor' => '30', 'rotulo' => 'Últimos 30 dias'],
                ['valor' => '90', 'rotulo' => 'Últimos 90 dias'],
                ['valor' => '180', 'rotulo' => 'Últimos 6 meses'],
                ['valor' => '365', 'rotulo' => 'Último ano'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function identidade(Customer $cliente): array
    {
        $ultimo = $this->escopo->orders()
            ->where('customer_id', $cliente->getKey())
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        $ativo = $ultimo instanceof Order
            && $ultimo->created_at !== null
            && $ultimo->created_at->greaterThanOrEqualTo($this->inicioDaAtividade());

        return [
            'nome' => $this->texto($cliente->name),
            'iniciais' => $this->iniciais($this->texto($cliente->name)),
            'cidadeUf' => $this->cidadeUf($this->textoOuNulo($cliente->getAttribute('city')), $this->textoOuNulo($cliente->getAttribute('state'))),
            'clienteDesde' => $this->data($cliente->created_at),
            'tipoDePessoa' => $this->rotulo('customer.person_type.'.$this->texto($cliente->person_type)) ?? self::VAZIO,
            'ativo' => $ativo,
            'situacao' => $ativo ? 'Ativo' : 'Inativo',
            'chip' => $ativo ? 'chip--ok' : 'chip--danger',
        ];
    }

    /**
     * Bloco "Dados cadastrais" da ficha — os mesmos campos do formulário da gaveta.
     *
     * @return list<array{rotulo: string, valor: string|null, icone: string}>
     */
    private function cadastro(Customer $cliente): array
    {
        $endereco = $this->textoOuNulo($cliente->getAttribute('address'));
        $cep = $this->textoOuNulo($cliente->getAttribute('postal_code'));

        return [
            ['rotulo' => 'CPF', 'valor' => $this->textoOuNulo($cliente->document), 'icone' => 'card'],
            ['rotulo' => 'Tipo de pessoa', 'valor' => $this->rotulo('customer.person_type.'.$this->texto($cliente->person_type)), 'icone' => 'user'],
            ['rotulo' => 'Razão social', 'valor' => $this->textoOuNulo($cliente->company_name), 'icone' => 'store'],
            ['rotulo' => 'Telefone/WhatsApp', 'valor' => $this->textoOuNulo($cliente->phone), 'icone' => 'whats'],
            ['rotulo' => 'E-mail', 'valor' => $this->textoOuNulo($cliente->email), 'icone' => 'mail'],
            ['rotulo' => 'Endereço', 'valor' => $endereco, 'icone' => 'pin'],
            ['rotulo' => 'Cidade/UF', 'valor' => $this->cidadeUf($this->textoOuNulo($cliente->getAttribute('city')), $this->textoOuNulo($cliente->getAttribute('state'))), 'icone' => 'globe'],
            ['rotulo' => 'CEP', 'valor' => $cep, 'icone' => 'pin'],
            ['rotulo' => 'Origem do contato', 'valor' => $this->origem($cliente), 'icone' => 'link'],
        ];
    }

    private function origem(Customer $cliente): ?string
    {
        $chave = $this->textoOuNulo($cliente->getAttribute('contact_source'));

        return $chave === null ? null : (self::ORIGENS_DO_CONTATO[$chave] ?? $chave);
    }

    /**
     * As três datas de relacionamento com a próxima ocorrência calculada.
     *
     * @return list<array{rotulo: string, data: string|null, proxima: string|null, faltam: int|null, icone: string}>
     */
    private function relacionamento(Customer $cliente): array
    {
        return [
            $this->dataDeRelacionamento('Aniversário', $cliente->getAttribute('birth_date'), 'calendar'),
            $this->dataDeRelacionamento('Aniversário de casamento', $cliente->getAttribute('wedding_date'), 'ring'),
            $this->dataDeRelacionamento('Início do namoro', $cliente->getAttribute('relationship_date'), 'sparkle'),
        ];
    }

    /**
     * @return array{rotulo: string, data: string|null, proxima: string|null, faltam: int|null, icone: string}
     */
    private function dataDeRelacionamento(string $rotulo, mixed $valor, string $icone): array
    {
        $momento = $this->momento($valor);

        if ($momento === null) {
            return ['rotulo' => $rotulo, 'data' => null, 'proxima' => null, 'faltam' => null, 'icone' => $icone];
        }

        $hoje = Carbon::today();
        $proxima = $momento->copy()->setYear($hoje->year)->startOfDay();

        if ($proxima->lessThan($hoje)) {
            $proxima->addYear();
        }

        return [
            'rotulo' => $rotulo,
            'data' => $this->data($momento),
            'proxima' => $this->data($proxima),
            'faltam' => (int) $hoje->diffInDays($proxima),
            'icone' => $icone,
        ];
    }

    /**
     * Estado atual do consentimento por tipo, mais o histórico.
     *
     * Regra 2 da tela 2.3: o consentimento é **registrável e revogável**, e por
     * isso mora em tabela própria com histórico, não num booleano no cliente. O
     * que vale é a última linha de cada tipo — e ela vale como concedida só se
     * `granted` for verdadeiro **e** não houver revogação. Uma linha com
     * `granted = true` e `revoked_at` preenchido é consentimento retirado, não
     * consentimento ativo.
     *
     * @return array<string, mixed>
     */
    private function consentimentos(Customer $cliente): array
    {
        /** @var EloquentCollection<int, CustomerConsent> $historico */
        $historico = $cliente->consents()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $porTipo = [];

        foreach ([CustomerConsent::TYPE_MARKETING, CustomerConsent::TYPE_TRANSACTIONAL] as $tipo) {
            $atual = $historico->firstWhere('type', $tipo);
            $valido = $atual instanceof CustomerConsent && $atual->granted === true && $atual->revoked_at === null;

            $porTipo[$tipo] = [
                'tipo' => $tipo,
                'rotulo' => (string) $this->rotulo('customer.consent_type.'.$tipo),
                'valido' => $valido,
                'situacao' => $this->situacaoDoConsentimento($atual, $valido),
                'chip' => $valido ? 'chip--ok' : 'chip--danger',
                'concedidoEm' => $this->dataHora($atual?->granted_at),
                'revogadoEm' => $this->dataHora($atual?->revoked_at),
                'canal' => $atual === null ? null : $this->textoOuNulo($atual->channel),
                'evidencia' => $atual === null ? null : $this->textoOuNulo($atual->evidence),
            ];
        }

        $porTipo['historico'] = $historico->map(fn (CustomerConsent $consentimento): array => [
            'rotulo' => (string) $this->rotulo('customer.consent_type.'.$this->texto($consentimento->type)),
            'concedido' => $consentimento->granted === true && $consentimento->revoked_at === null,
            'quando' => $this->dataHora($consentimento->revoked_at ?? $consentimento->granted_at ?? $consentimento->created_at),
            'acao' => $consentimento->revoked_at !== null ? 'Revogado' : 'Concedido',
            'canal' => $this->textoOuNulo($consentimento->channel),
            'evidencia' => $this->textoOuNulo($consentimento->evidence),
        ])->all();

        return $porTipo;
    }

    private function situacaoDoConsentimento(?CustomerConsent $consentimento, bool $valido): string
    {
        if ($consentimento === null) {
            return 'Sem registro';
        }

        return $valido ? 'Concedido' : 'Revogado';
    }

    /**
     * Regra 1 da tela 2.3 (LGPD): **data de casamento e de namoro só alimentam
     * campanha com consentimento de marketing válido.**
     *
     * O bloqueio é feito aqui, na origem, e não com um `@if` na view: sem
     * consentimento a lista de datas de campanha sai **vazia** do service, então
     * não há o que uma tela — ou um export, ou um job de disparo que venha a ler
     * este mesmo método — deixar escapar por engano. As datas continuam visíveis
     * no bloco de cadastro, porque ali são dado do cliente, não gatilho de envio.
     *
     * @return array{liberado: bool, motivo: string, datas: list<array<string, mixed>>}
     */
    private function campanhas(Customer $cliente, bool $marketingValido): array
    {
        if (! $marketingValido) {
            return [
                'liberado' => false,
                'motivo' => 'Sem consentimento de marketing válido: as datas de casamento e namoro não alimentam campanha.',
                'datas' => [],
            ];
        }

        $datas = array_values(array_filter(
            $this->relacionamento($cliente),
            static fn (array $data): bool => $data['data'] !== null,
        ));

        return [
            'liberado' => true,
            'motivo' => 'Consentimento de marketing registrado: as datas especiais podem alimentar campanha.',
            'datas' => $datas,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function resumoDeCompra(Customer $cliente): array
    {
        $chave = $cliente->getKey();

        // `orders()` devolve uma relação nova a cada chamada, então as três
        // contas partem do mesmo `WHERE reseller_id` sem uma sujar a outra.
        $total = (float) $this->escopo->orders()->where('customer_id', $chave)->sum('total_amount');
        $pedidos = $this->escopo->orders()->where('customer_id', $chave)->count();
        $emAberto = $this->escopo->orders()
            ->where('customer_id', $chave)
            ->whereIn('operational_status', StatusDoPedido::EM_ABERTO)
            ->count();

        return [
            'pedidos' => (string) $pedidos,
            'emAberto' => (string) $emAberto,
            'total' => $this->dinheiro($total),
            'ticket' => $this->dinheiro($pedidos > 0 ? $total / $pedidos : 0.0),
        ];
    }

    /**
     * Histórico de pedidos daquele cliente, escopado pelo revendedor.
     *
     * @return EloquentCollection<int, Order>
     */
    private function pedidosDoCliente(Customer $cliente): EloquentCollection
    {
        /** @var EloquentCollection<int, Order> $pedidos */
        $pedidos = $this->escopo->orders()
            ->where('customer_id', $cliente->getKey())
            ->withCount('items')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(self::PEDIDOS_NA_FICHA)
            ->get();

        return $pedidos;
    }

    /**
     * @param  EloquentCollection<int, Order>  $pedidos
     * @return list<array<string, mixed>>
     */
    private function linhasDePedido(EloquentCollection $pedidos): array
    {
        return $pedidos->map(fn (Order $pedido): array => [
            'numero' => $this->texto($pedido->public_number),
            'data' => $this->data($pedido->created_at),
            'hora' => $this->hora($pedido->created_at),
            'itens' => (int) $pedido->getAttribute('items_count'),
            'valor' => $this->dinheiro($pedido->total_amount),
            'operacional' => $this->status->operacional($this->textoOuNulo($pedido->operational_status)),
            'pagamento' => $this->status->pagamento($this->textoOuNulo($pedido->payment_status)),
            'previsao' => $this->data($pedido->getAttribute('expected_at')),
            'url' => route('portal.pedidos.show', $pedido),
        ])->all();
    }

    /**
     * @param  array{q: string|null, situacao: string|null, cidade: string|null, uf: string|null, local: string|null, periodo: int}  $filtros
     */
    private function temFiltro(array $filtros): bool
    {
        return $filtros['q'] !== null
            || $filtros['situacao'] !== null
            || $filtros['local'] !== null
            || $filtros['periodo'] > 0;
    }

    private function inicioDaAtividade(): Carbon
    {
        return Carbon::now()->subDays(self::JANELA_ATIVIDADE_DIAS);
    }

    /**
     * Repete o `WHERE orders.reseller_id` dentro de subconsultas e de `whereHas`.
     *
     * Genérico porque o `whereHas` entrega o construtor tipado como
     * `Builder<Model>` e a subconsulta como `Builder<Order>` — é a mesma cláusula
     * nos dois casos.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $consulta
     * @return Builder<TModel>
     */
    private function doRevendedor(Builder $consulta): Builder
    {
        return $consulta->where('orders.reseller_id', $this->escopo->reseller->getKey());
    }
}
