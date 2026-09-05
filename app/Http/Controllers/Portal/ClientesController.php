<?php

/*
[Modulo: app/Http/Controllers/Portal]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Tela 2.3 do Portal: a carteira de clientes do lojista e a ficha com relacionamento, LGPD e historico de pedidos.
*/

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\ClientesFiltroRequest;
use App\Models\Customer;
use App\Services\Portal\ClientesService;
use App\Support\ResellerScope;
use Illuminate\View\View;

/**
 * Tela 2.3 — Clientes / CRM.
 *
 * O `{customer}` do detalhe já chega verificado: {@see ResellerScope}
 * resolve o parâmetro dentro do escopo do revendedor autenticado e devolve **404**
 * — nunca 403 — quando o registro é de outra carteira. Este controller não repete
 * a checagem porque não há como chegar aqui sem ela; o que o segura é
 * `ClientesTest::test_cliente_de_outro_lojista_devolve_404`.
 */
class ClientesController extends Controller
{
    public function __construct(private readonly ClientesService $clientes) {}

    public function index(ClientesFiltroRequest $request): View
    {
        return view('portal.clientes.index', $this->clientes->montarIndice($request->filtros()));
    }

    public function show(Customer $customer): View
    {
        return view('portal.clientes.show', $this->clientes->montarFicha($customer));
    }
}
