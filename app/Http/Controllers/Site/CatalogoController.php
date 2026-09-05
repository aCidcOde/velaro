<?php

/*
[Modulo: app/Http/Controllers/Site]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Catalogo publico da Velaro: grade filtravel por colecao e ficha do modelo, ambas sem preco.
*/

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Requests\Site\CatalogoFiltroRequest;
use App\Models\Product;
use App\Services\Site\CatalogoPublicoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CatalogoController extends Controller
{
    public function __construct(private readonly CatalogoPublicoService $catalogo) {}

    /**
     * Tela 1.3 — lista. A colecao e um segmento de rota (`/catalogo/diamond`);
     * quando o `select` da barra de filtros a manda por query string, o pedido e
     * devolvido para a URL canonica com os demais filtros preservados.
     */
    public function index(CatalogoFiltroRequest $request, ?string $colecao = null): View|RedirectResponse
    {
        $filtros = $request->filtros();

        if ($request->informouColecaoNaQuery()) {
            return redirect()->route('site.catalogo', $this->catalogo->parametrosDeRota($filtros));
        }

        return view('site.catalogo.index', $this->catalogo->montarIndice($filtros, $colecao));
    }

    /**
     * Tela 1.3 — detalhe do modelo. O service reabre o produto pela projecao
     * publica, entao `products.price` (custo B2B) nao chega na view.
     */
    public function produto(Product $product): View
    {
        return view('site.catalogo.produto', $this->catalogo->montarProduto($product));
    }
}
