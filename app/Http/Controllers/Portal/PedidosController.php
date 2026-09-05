<?php

/*
[Modulo: app/Http/Controllers/Portal]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Telas 2.5 e 2.11 do Portal: lista filtravel de pedidos e o detalhe, com o bloco de retirada no estado pronto.
*/

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\PedidosFiltroRequest;
use App\Models\Order;
use App\Services\Portal\PedidosService;
use App\Support\ResellerScope;
use Illuminate\View\View;

/**
 * Telas 2.5 (lista e detalhe do pedido) e 2.11 (o mesmo detalhe no estado
 * "pronto para retirada" — é um estado, não uma rota à parte).
 *
 * A rota é sempre por `public_number`; `orders.id` não é exposto (§4.5). O
 * binding do `{order:public_number}` é o escopado de
 * {@see ResellerScope}: o pedido de outro lojista responde 404 com o
 * mesmo corpo de um número inexistente, para que percorrer a faixa de números não
 * revele nada sobre a carteira do concorrente.
 */
class PedidosController extends Controller
{
    public function __construct(private readonly PedidosService $pedidos) {}

    public function index(PedidosFiltroRequest $request): View
    {
        return view('portal.pedidos.index', $this->pedidos->montarIndice($request->filtros()));
    }

    public function show(Order $order): View
    {
        return view('portal.pedidos.show', $this->pedidos->montarDetalhe($order));
    }
}
