<?php

/*
[Modulo: app/Http/Controllers/Backend]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Tela 3.6 do Painel Master: o ciclo completo do pedido — abas, lista, detalhe e o cadastro manual.
*/

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\PedidoCriarRequest;
use App\Models\Order;
use App\Services\Backend\PedidoService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PedidosController extends Controller
{
    public function __construct(private readonly PedidoService $pedidos) {}

    public function index(Request $request): View
    {
        $this->exigirPermissao($request, 'velaro.orders.view');

        $filtros = [
            'aba' => $this->pedidos->aba($request->string('aba')->toString() ?: null),
            'busca' => $request->string('busca')->toString(),
            'status' => $request->string('status')->toString(),
            'periodo' => (int) $request->input('periodo', 30),
        ];

        $pedidos = $this->pedidos->listar($filtros);
        $selecionado = $this->pedidoSelecionado($request, $pedidos);

        return view('backend.velaro.pedidos.index', [
            'kpis' => $this->pedidos->kpis(),
            'pedidos' => $pedidos,
            'statusDisponiveis' => $this->pedidos->opcoesDeStatus(),
            'chips' => $this->pedidos->chipsOperacionais(),
            'filtros' => $filtros,
            'selecionado' => $selecionado,
            'detalhe' => $selecionado instanceof Order
                ? array_merge($this->pedidos->detalhe($selecionado), [
                    'pedido' => $selecionado,
                    'podeAtualizarStatus' => $request->user()?->hasBackendPermission('velaro.orders.update_status') === true,
                    'podeConfirmarRetirada' => $request->user()?->hasBackendPermission('velaro.orders.confirm_pickup') === true,
                    'podeConfirmarRetiradaDoLote' => $request->user()?->hasBackendPermission('velaro.orders.confirm_batch_pickup') === true,
                ])
                : null,
            'podeCriar' => $request->user()?->hasBackendPermission('velaro.orders.update_status') === true,
        ]);
    }

    public function show(Request $request, Order $order): View
    {
        $this->exigirPermissao($request, 'velaro.orders.view');

        return view('backend.velaro.pedidos.show', array_merge($this->pedidos->detalhe($order), [
            'pedido' => $order,
            // O projeto resolve permissao granular por `hasBackendPermission`, e
            // nao por Gate: `can()` sem gate definido devolve false em silencio.
            // Os botoes de escrita saem daqui, e nao de um @can que nunca acenderia.
            'podeAtualizarStatus' => $request->user()?->hasBackendPermission('velaro.orders.update_status') === true,
            'podeConfirmarRetirada' => $request->user()?->hasBackendPermission('velaro.orders.confirm_pickup') === true,
            'podeConfirmarRetiradaDoLote' => $request->user()?->hasBackendPermission('velaro.orders.confirm_batch_pickup') === true,
        ]));
    }

    public function create(Request $request): View
    {
        // O formulario so existe para escrever; quem nao pode criar nao abre a tela.
        $this->exigirPermissao($request, 'velaro.orders.update_status');

        return view('backend.velaro.pedidos.create', $this->pedidos->dadosDoFormulario());
    }

    public function store(PedidoCriarRequest $request): RedirectResponse
    {
        /** @var array<string, mixed> $dados */
        $dados = $request->validated();

        $pedido = $this->pedidos->criar($dados, $request->user());

        return redirect()
            ->route('backend.pedidos.show', $pedido)
            ->with('status', 'Pedido '.$pedido->public_number.' criado em nome do revendedor. O registro ficou na trilha de auditoria.');
    }

    /**
     * O pedido que a coluna do meio abre: o de `?pedido=` ou, sem parâmetro, o
     * primeiro da página — como o protótipo mostra a tela, lista à esquerda e
     * detalhe à direita.
     *
     * @param  LengthAwarePaginator<int, Order>  $pedidos
     */
    private function pedidoSelecionado(Request $request, LengthAwarePaginator $pedidos): ?Order
    {
        $numero = $request->string('pedido')->toString();

        if ($numero !== '') {
            return Order::query()->where('public_number', $numero)->first();
        }

        $primeiro = $pedidos->items()[0] ?? null;

        return $primeiro instanceof Order ? $primeiro : null;
    }

    private function exigirPermissao(Request $request, string $permissao): void
    {
        abort_unless($request->user()?->hasBackendPermission($permissao) === true, 403);
    }
}
