<?php

/*
[Modulo: app/Http/Controllers/Backend]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Tela 3.4 do Painel Master: saldo por SKU, aro e local, a gaveta do item, o extrato e as cinco movimentacoes.
*/

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\EstoqueMovimentacaoRequest;
use App\Models\Product;
use App\Models\ProductionRequest;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Services\Backend\EstoqueService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EstoqueController extends Controller
{
    public function __construct(private readonly EstoqueService $estoque) {}

    public function index(Request $request): View
    {
        $this->exigirPermissao($request, 'velaro.stock.view');

        $filtros = [
            'busca' => $request->string('busca')->toString(),
            'categoria' => $request->string('categoria')->toString(),
            'situacao' => $request->string('situacao')->toString(),
            'local' => $request->string('local')->toString(),
        ];

        $itens = $this->estoque->listar($filtros);

        return view('backend.velaro.estoque.index', [
            'kpis' => $this->estoque->kpis(),
            'itens' => $itens,
            'filtros' => $filtros,
            'opcoes' => $this->estoque->opcoesDeFiltro(),
            'gaveta' => $this->estoque->gaveta($this->produtoDaGaveta($request, $itens), $filtros['local']),
            'podeAjustar' => $request->user()?->hasBackendPermission('velaro.stock.adjust') === true,
            'podeSolicitarProducao' => $request->user()?->hasBackendPermission('velaro.stock.request_production') === true,
        ]);
    }

    /**
     * Formulário da nova movimentação (mockup 52a).
     *
     * A tela existe só para escrever: quem não pode ajustar saldo nem abrir
     * ordem de produção não tem o que fazer nela, e recebe 403 em vez de um
     * formulário que o POST recusaria depois.
     */
    public function create(Request $request): View
    {
        $podeAjustar = $request->user()?->hasBackendPermission('velaro.stock.adjust') === true;
        $podeSolicitarProducao = $request->user()?->hasBackendPermission('velaro.stock.request_production') === true;

        abort_unless($podeAjustar || $podeSolicitarProducao, 403);

        $variante = ProductVariant::query()->find($request->integer('variante'));
        $tipo = $this->tipoEscolhido($request);

        return view('backend.velaro.estoque.movimentacao', [
            'tipo' => $tipo,
            'variantes' => $this->estoque->variantesDoCatalogo(),
            'opcoes' => $this->estoque->opcoesDeFiltro(),
            // A reserva é o único tipo que pede pedido vinculado; carregar a
            // lista nos outros quatro seria uma consulta que a tela não usa.
            'pedidos' => $tipo === StockMovement::TYPE_RESERVATION
                ? $this->estoque->pedidosQuePodemReservar()
                : null,
            'ficha' => $this->estoque->fichaDaVariante($variante, $request->string('local')->toString()),
            'podeAjustar' => $podeAjustar,
            'podeSolicitarProducao' => $podeSolicitarProducao,
        ]);
    }

    public function store(EstoqueMovimentacaoRequest $request): RedirectResponse
    {
        /** @var array<string, mixed> $dados */
        $dados = $request->validated();

        $registro = $this->estoque->registrarMovimentacao($dados, $request->user());

        $variante = $registro instanceof ProductionRequest
            ? $registro->product_variant_id
            : $registro->stockItem?->product_variant_id;

        $recado = $registro instanceof ProductionRequest
            ? 'Solicitação de produção aberta. A quantidade entra em "sob encomenda" até a bancada entregar.'
            : 'Movimentação registrada com saldo anterior e posterior na trilha de auditoria.';

        return redirect()
            ->route('backend.estoque.historico', ['variant' => $variante])
            ->with('status', $recado);
    }

    /**
     * Extrato do item (mockup 52b).
     *
     * A rota entrega um aro, e o protótipo mostra o extrato do produto inteiro
     * com o aro como coluna e como filtro — por isso a variante da rota entra
     * como valor inicial do filtro em vez de recortar o extrato.
     */
    public function historico(Request $request, ProductVariant $variant): View
    {
        $this->exigirPermissao($request, 'velaro.stock.view');

        $variant->loadMissing('product');
        /** @var Product $produto */
        $produto = $variant->product;

        $filtros = [
            'busca' => $request->string('busca')->toString(),
            'tipo' => $request->string('tipo')->toString(),
            'aro' => $request->has('aro') ? $request->string('aro')->toString() : (string) $variant->getAttribute('ring_size'),
            'periodo' => (int) $request->input('periodo', 30),
        ];

        return view('backend.velaro.estoque.historico', [
            'variante' => $variant,
            'produto' => $produto,
            'ficha' => $this->estoque->fichaDaVariante($variant),
            'resumo' => $this->estoque->gaveta($produto),
            'kpis' => $this->estoque->kpisDoExtrato($produto, $filtros['periodo']),
            'movimentacoes' => $this->estoque->extrato($produto, $filtros),
            'reservas' => $this->estoque->reservasEmAberto($produto),
            'opcoes' => $this->estoque->opcoesDeFiltro(),
            'filtros' => $filtros,
            'podeAjustar' => $request->user()?->hasBackendPermission('velaro.stock.adjust') === true,
        ]);
    }

    /**
     * O produto que a gaveta abre: o de `?produto=` ou, sem parâmetro, o
     * primeiro da página — como o protótipo mostra a tela.
     *
     * @param  LengthAwarePaginator<int, Product>  $itens
     */
    private function produtoDaGaveta(Request $request, LengthAwarePaginator $itens): ?Product
    {
        $escolhido = $request->integer('produto');

        if ($escolhido > 0) {
            return Product::query()->find($escolhido);
        }

        $primeiro = $itens->items()[0] ?? null;

        return $primeiro instanceof Product ? $primeiro : null;
    }

    /**
     * A aba de tipo do formulário 52a. Valor fora do vocabulário cai em
     * "entrada", que é a aba que o protótipo abre selecionada.
     */
    private function tipoEscolhido(Request $request): string
    {
        $tipo = $request->string('tipo')->toString();

        $tipos = [
            StockMovement::TYPE_INBOUND,
            StockMovement::TYPE_OUTBOUND,
            StockMovement::TYPE_ADJUSTMENT,
            StockMovement::TYPE_PRODUCTION,
            StockMovement::TYPE_RESERVATION,
        ];

        return in_array($tipo, $tipos, true) ? $tipo : StockMovement::TYPE_INBOUND;
    }

    private function exigirPermissao(Request $request, string $permissao): void
    {
        abort_unless($request->user()?->hasBackendPermission($permissao) === true, 403);
    }
}
