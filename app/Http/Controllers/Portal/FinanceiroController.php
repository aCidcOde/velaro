<?php

/*
[Modulo: app/Http/Controllers/Portal]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Financeiro do lojista: lotes semanais devidos a Velaro, notas fiscais emitidas e o pagamento do lote.
*/

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\FinanceiroFiltroRequest;
use App\Http\Requests\Portal\NotasFiltroRequest;
use App\Http\Requests\Portal\PagamentoLoteRequest;
use App\Models\OrderBatch;
use App\Services\Portal\FinanceiroService;
use App\Services\Portal\LotePagamentoService;
use App\Services\Portal\NotasFiscaisService;
use App\Support\ResellerScope;
use Illuminate\View\View;

/**
 * Tela 2.4 e as duas telas internas que saem dela.
 *
 * O escopo nao e checado aqui: `ResellerScope` chega pelo container ja preso ao
 * revendedor autenticado (`scoped`), e `{batch}` chega pelo bind escopado — lote
 * de outro lojista morre em 404 antes do metodo rodar. O controller so escolhe o
 * service e a view.
 */
class FinanceiroController extends Controller
{
    public function __construct(
        private readonly ResellerScope $escopo,
        private readonly FinanceiroService $financeiro,
    ) {}

    /**
     * `GET /portal/financeiro` — lotes, pedidos com o custo Velaro e o drawer de
     * pagamento do lote em aberto.
     */
    public function index(FinanceiroFiltroRequest $request): View
    {
        return view('portal.financeiro.index', $this->financeiro->montarIndice($this->escopo, $request->aba(), $request->pagina()));
    }

    /**
     * `GET /portal/financeiro/notas` — as NF-e que a Velaro emitiu contra a loja.
     */
    public function notas(NotasFiltroRequest $request, NotasFiscaisService $notas): View
    {
        return view('portal.financeiro.notas', $notas->montar($this->escopo, $request->filtros()));
    }

    /**
     * `GET /portal/financeiro/lotes/{batch}/pagamento` — meios de pagamento do
     * lote. Tela de exibicao: mostra a cobranca que ja existe e o comprovante,
     * sem processar pagamento nem falar com gateway nenhum.
     */
    public function pagamento(PagamentoLoteRequest $request, OrderBatch $batch, LotePagamentoService $pagamento): View
    {
        return view('portal.financeiro.pagamento', $pagamento->montar($batch, $request));
    }
}
