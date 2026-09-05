<?php

/*
[Modulo: app/Services/Portal]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Monta a tela 2.1: indicadores, ultimos pedidos, pendencias, cartao da vitrine e checklist de configuracao do lojista.
*/

namespace App\Services\Portal;

use App\Models\Order;
use App\Models\Reseller;
use App\Models\ResellerPriceSetting;
use App\Models\ResellerStore;
use App\Models\SupportTicket;
use App\Services\Portal\Concerns\FormataDados;
use App\Support\ResellerScope;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

/**
 * Dashboard do lojista.
 *
 * **Nenhum número desta tela sai de uma query solta.** Todo somatório nasce das
 * relações do próprio revendedor, pelo {@see ResellerScope}: `orders()`,
 * `customers()` e `tickets()` já chegam com o `WHERE reseller_id` embutido, e é
 * por isso que não há um `Order::query()` sequer neste arquivo. A regra 2 da
 * tela 2.1 é literal — vazamento entre revendedores é falha crítica, e a forma
 * de não errar é não ter por onde.
 *
 * Os dois status do pedido são independentes (decisão 1.2 do banco):
 * `operational_status` diz onde a peça está, `payment_status` diz se o lote foi
 * quitado. A tela mostra os dois lado a lado justamente porque um não implica o
 * outro — o aviso ao pé da tabela existe para explicar isso ao lojista. O rótulo
 * e a cor do chip vêm de {@see StatusDoPedido}, o mesmo vocabulário que a lista
 * de pedidos usa: o "Em produção" do dashboard e o da tela 2.5 são a mesma
 * string e o mesmo tom, porque saem do mesmo lugar.
 */
class PainelLojistaService
{
    use FormataDados;

    /**
     * Pagamento em aberto com a Velaro. Estornado e cancelado ficam de fora: não
     * há o que cobrar neles.
     *
     * @var list<string>
     */
    private const PAGAMENTO_EM_ABERTO = [
        Order::PAYMENT_STATUS_PENDING,
        Order::PAYMENT_STATUS_AWAITING_CLEARANCE,
        Order::PAYMENT_STATUS_OVERDUE,
    ];

    /**
     * Chamado aberto é todo o que ainda não foi resolvido.
     *
     * @var list<string>
     */
    private const CHAMADO_ABERTO = [
        SupportTicket::STATUS_OPEN,
        SupportTicket::STATUS_IN_PROGRESS,
        SupportTicket::STATUS_AWAITING_CUSTOMER,
        SupportTicket::STATUS_UNDER_REVIEW,
        SupportTicket::STATUS_ANSWERED,
    ];

    /**
     * Chamado que está parado esperando o lojista responder — é o que vira
     * pendência na coluna do meio.
     *
     * @var list<string>
     */
    private const CHAMADO_COM_A_BOLA = [
        SupportTicket::STATUS_AWAITING_CUSTOMER,
        SupportTicket::STATUS_ANSWERED,
    ];

    /** Linhas da tabela "Últimos pedidos". */
    private const ULTIMOS_PEDIDOS = 5;

    public function __construct(
        private readonly ResellerScope $escopo,
        private readonly StatusDoPedido $status,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function montar(): array
    {
        $revendedor = $this->escopo->reseller;
        $loja = $this->escopo->store();
        $precos = $revendedor->priceSetting()->first();

        $porOperacional = $this->contarPor('operational_status');
        $porPagamento = $this->contarPor('payment_status');

        return [
            'revendedor' => $revendedor,
            'saudacao' => $this->saudacao($revendedor),
            'indicadores' => $this->indicadores($porOperacional, $porPagamento),
            'atalhos' => $this->atalhos(),
            'ultimosPedidos' => $this->ultimosPedidos(),
            'pendencias' => $this->pendencias($loja, $precos),
            'vitrine' => $this->vitrine($loja),
            'checklist' => $this->checklist($loja, $precos),
        ];
    }

    /**
     * Os seis indicadores do topo, na ordem do protótipo.
     *
     * @param  array<string, int>  $porOperacional
     * @param  array<string, int>  $porPagamento
     * @return list<array{icone: string, variante: string, rotulo: string, valor: int, acao: array{rotulo: string, url: string}}>
     */
    private function indicadores(array $porOperacional, array $porPagamento): array
    {
        $pedidos = route('portal.pedidos.index');

        return [
            [
                'icone' => 'bag',
                'variante' => 'kpi__icon--gold',
                'rotulo' => 'Pedidos em andamento',
                'valor' => $this->somar($porOperacional, StatusDoPedido::EM_ABERTO),
                'acao' => ['rotulo' => 'Ver pedidos →', 'url' => $pedidos],
            ],
            [
                'icone' => 'sparkle',
                'variante' => 'kpi__icon--violet',
                'rotulo' => 'Em produção',
                'valor' => $porOperacional[Order::OPERATIONAL_STATUS_IN_PRODUCTION] ?? 0,
                'acao' => ['rotulo' => 'Ver pedidos →', 'url' => route('portal.pedidos.index', ['status' => Order::OPERATIONAL_STATUS_IN_PRODUCTION])],
            ],
            [
                'icone' => 'truck',
                'variante' => 'kpi__icon--ok',
                'rotulo' => 'Prontos para retirada',
                'valor' => $porOperacional[Order::OPERATIONAL_STATUS_READY_FOR_PICKUP] ?? 0,
                'acao' => ['rotulo' => 'Ver pedidos →', 'url' => route('portal.pedidos.index', ['status' => Order::OPERATIONAL_STATUS_READY_FOR_PICKUP])],
            ],
            [
                'icone' => 'card',
                'variante' => 'kpi__icon--danger',
                'rotulo' => 'Aguardando pagamento à Velaro',
                'valor' => $this->somar($porPagamento, self::PAGAMENTO_EM_ABERTO),
                'acao' => ['rotulo' => 'Ver detalhes →', 'url' => route('portal.financeiro.index')],
            ],
            [
                'icone' => 'support',
                'variante' => 'kpi__icon--warn',
                'rotulo' => 'Chamados abertos',
                'valor' => $this->escopo->tickets()->whereIn('status', self::CHAMADO_ABERTO)->count(),
                'acao' => ['rotulo' => 'Ver chamados →', 'url' => route('portal.suporte.index')],
            ],
            [
                'icone' => 'users',
                'variante' => 'kpi__icon--info',
                'rotulo' => 'Clientes cadastrados',
                'valor' => $this->escopo->customers()->count(),
                'acao' => ['rotulo' => 'Ver clientes →', 'url' => route('portal.clientes.index')],
            ],
        ];
    }

    /**
     * Os cinco atalhos da faixa escura.
     *
     * @return list<array{icone: string, titulo: string, descricao: string, url: string}>
     */
    private function atalhos(): array
    {
        return [
            ['icone' => 'bag', 'titulo' => 'Novo pedido', 'descricao' => 'Criar um novo pedido para seu cliente', 'url' => route('portal.pedidos.index')],
            ['icone' => 'book', 'titulo' => 'Catálogo Revendedor', 'descricao' => 'Acesse o catálogo completo de alianças', 'url' => route('portal.catalogo')],
            ['icone' => 'store', 'titulo' => 'Vitrine da loja', 'descricao' => 'Visualize sua vitrine para clientes', 'url' => route('portal.vitrine')],
            ['icone' => 'coin', 'titulo' => 'Financeiro', 'descricao' => 'Acompanhe pagamentos e cobranças com a Velaro', 'url' => route('portal.financeiro.index')],
            ['icone' => 'user-plus', 'titulo' => 'Cadastrar cliente', 'descricao' => 'Adicione um novo cliente final', 'url' => route('portal.clientes.index')],
        ];
    }

    /**
     * Tabela "Últimos pedidos".
     *
     * @return list<array{numero: string, url: string, cliente: string, operacional: array{rotulo: string, chip: string}, pagamento: array{rotulo: string, chip: string}, custo: string, previsao: string|null}>
     */
    private function ultimosPedidos(): array
    {
        /** @var EloquentCollection<int, Order> $pedidos */
        $pedidos = $this->escopo->orders()
            ->with('customer')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(self::ULTIMOS_PEDIDOS)
            ->get();

        return $pedidos->map(function (Order $pedido): array {
            $previsao = $pedido->getAttribute('expected_at');
            $total = $pedido->getAttribute('total_amount');

            return [
                'numero' => (string) $pedido->getRouteKey(),
                'url' => route('portal.pedidos.show', $pedido),
                // O pedido pode não ter cliente final vinculado (venda de balcão
                // registrada antes do cadastro): a coluna mostra o travessão.
                'cliente' => $this->textoOuNulo($pedido->customer?->name) ?? self::VAZIO,
                'operacional' => $this->status->operacional($this->textoOuNulo($pedido->getAttribute('operational_status'))),
                'pagamento' => $this->status->pagamento($this->textoOuNulo($pedido->getAttribute('payment_status'))),
                'custo' => $this->dinheiro(is_numeric($total) ? (float) $total : 0.0),
                'previsao' => $this->data($previsao),
            ];
        })->all();
    }

    /**
     * "Ações pendentes": o que está parado esperando o lojista.
     *
     * A lista é derivada do estado real da conta — nada aqui é fixo. Conta vazia
     * e em dia devolve lista vazia, e a coluna mostra o próprio vazio em vez de
     * inventar tarefa.
     *
     * @return list<array{icone: string, variante: string, titulo: string, descricao: string, acao: array{rotulo: string, url: string, estilo: string}}>
     */
    private function pendencias(?ResellerStore $loja, ?ResellerPriceSetting $precos): array
    {
        $pendencias = [];

        if (! $this->logoDefinida($loja)) {
            $pendencias[] = [
                'icone' => 'store',
                'variante' => '',
                'titulo' => 'Cadastrar logo da loja',
                'descricao' => 'Personalize sua vitrine com sua marca.',
                'acao' => ['rotulo' => 'Fazer agora', 'url' => route('portal.loja.edit'), 'estilo' => 'btn--secondary'],
            ];
        }

        if (! $this->margemDefinida($precos)) {
            $pendencias[] = [
                'icone' => 'tag',
                'variante' => '',
                'titulo' => 'Definir markup padrão',
                'descricao' => 'Configure sua margem padrão de venda.',
                'acao' => ['rotulo' => 'Configurar', 'url' => route('portal.precos.edit'), 'estilo' => 'btn--secondary'],
            ];
        }

        $chamado = $this->escopo->tickets()
            ->whereIn('status', self::CHAMADO_COM_A_BOLA)
            ->orderByDesc('updated_at')
            ->first();

        if ($chamado instanceof SupportTicket) {
            $pendencias[] = [
                'icone' => 'support',
                'variante' => 'kpi__icon--warn',
                'titulo' => 'Responder chamado '.$chamado->getRouteKey(),
                'descricao' => (string) $chamado->getAttribute('subject'),
                'acao' => ['rotulo' => 'Ver chamado', 'url' => route('portal.suporte.show', $chamado), 'estilo' => 'btn--secondary'],
            ];
        }

        $emAberto = $this->escopo->orders()
            ->whereIn('payment_status', self::PAGAMENTO_EM_ABERTO)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        if ($emAberto instanceof Order) {
            $pendencias[] = [
                'icone' => 'card',
                'variante' => 'kpi__icon--danger',
                'titulo' => 'Confirmar pagamento do pedido '.$emAberto->getRouteKey(),
                'descricao' => 'Aguardando confirmação para liberar produção.',
                'acao' => ['rotulo' => 'Ver pedido', 'url' => route('portal.pedidos.show', $emAberto), 'estilo' => 'btn--primary'],
            ];
        }

        return $pendencias;
    }

    /**
     * Cartão "Vitrine da sua loja".
     *
     * @return array{nome: string, slogan: string, dominio: string|null, publicada: bool, url: string}
     */
    private function vitrine(?ResellerStore $loja): array
    {
        $revendedor = $this->escopo->reseller;
        $nome = $this->texto($loja?->name) !== '' ? $this->texto($loja?->name) : $this->texto($revendedor->trade_name);
        $dominio = $this->texto($loja?->domain);

        return [
            'nome' => $nome !== '' ? $nome : $this->texto($revendedor->legal_name),
            'slogan' => $this->texto($loja?->slogan) !== ''
                ? $this->texto($loja?->slogan)
                : 'Símbolo de amor. Feito para a vida toda.',
            'dominio' => $dominio !== '' ? $dominio : null,
            'publicada' => $this->vitrinePublicada($loja),
            'url' => route('portal.vitrine'),
        ];
    }

    /**
     * Checklist "Configuração da loja" — os três passos que destravam a venda.
     *
     * @return array{itens: list<array{rotulo: string, feito: bool, acao: array{rotulo: string, url: string, estilo: string}}>, feitos: int, total: int, percentual: int}
     */
    private function checklist(?ResellerStore $loja, ?ResellerPriceSetting $precos): array
    {
        // O passo feito vira "Editar" e sai do caminho; o que falta fica com o
        // verbo do que precisa ser feito e com o botão forte. Sem isso os três
        // itens dizem a mesma coisa e o checklist para de dirigir a atenção.
        $itens = [
            $this->passo('Personalizar logo da loja', $this->logoDefinida($loja), 'Enviar logo', route('portal.loja.edit')),
            $this->passo('Definir margem padrão', $this->margemDefinida($precos), 'Configurar', route('portal.precos.edit')),
            $this->passo('Ativar vitrine para clientes', $this->vitrinePublicada($loja), 'Ativar', route('portal.loja.edit')),
        ];

        $total = count($itens);
        $feitos = count(array_filter($itens, static fn (array $item): bool => $item['feito']));

        return [
            'itens' => $itens,
            'feitos' => $feitos,
            'total' => $total,
            'percentual' => $total > 0 ? (int) round($feitos / $total * 100) : 0,
        ];
    }

    /**
     * Um passo do checklist, com o rótulo e o peso do botão já resolvidos pelo
     * estado do passo.
     *
     * @return array{rotulo: string, feito: bool, acao: array{rotulo: string, url: string, estilo: string}}
     */
    private function passo(string $rotulo, bool $feito, string $verbo, string $url): array
    {
        return [
            'rotulo' => $rotulo,
            'feito' => $feito,
            'acao' => [
                'rotulo' => $feito ? 'Editar' : $verbo,
                'url' => $url,
                'estilo' => $feito ? 'btn--secondary' : 'btn--primary',
            ],
        ];
    }

    /**
     * Contagem agregada por coluna de status, em uma query só.
     *
     * @return array<string, int>
     */
    private function contarPor(string $coluna): array
    {
        /** @var array<string, mixed> $linhas */
        $linhas = $this->escopo->orders()
            ->getQuery()
            ->select($coluna)
            ->selectRaw('COUNT(*) as total')
            ->groupBy($coluna)
            ->pluck('total', $coluna)
            ->all();

        $contagem = [];

        foreach ($linhas as $status => $total) {
            $contagem[(string) $status] = is_numeric($total) ? (int) $total : 0;
        }

        return $contagem;
    }

    /**
     * @param  array<string, int>  $contagem
     * @param  list<string>  $status
     */
    private function somar(array $contagem, array $status): int
    {
        $total = 0;

        foreach ($status as $chave) {
            $total += $contagem[$chave] ?? 0;
        }

        return $total;
    }

    private function logoDefinida(?ResellerStore $loja): bool
    {
        return $loja !== null && $this->texto($loja->logo_path) !== '';
    }

    private function margemDefinida(?ResellerPriceSetting $precos): bool
    {
        return $precos !== null && $precos->getAttribute('margin_global') !== null;
    }

    private function vitrinePublicada(?ResellerStore $loja): bool
    {
        return $loja !== null
            && (bool) $loja->getAttribute('is_active')
            && $loja->getAttribute('published_at') !== null;
    }

    private function saudacao(Reseller $revendedor): string
    {
        $nome = $this->texto($revendedor->trade_name);

        return $nome !== ''
            ? sprintf('Bem-vindo à sua plataforma B2B Velaro, %s.', $nome)
            : 'Bem-vindo à sua plataforma B2B Velaro.';
    }
}
