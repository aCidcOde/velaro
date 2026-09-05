<?php

/*
[Modulo: app/Http/Controllers/Portal]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Catalogo revendedor: a unica tela em que o custo B2B da Velaro aparece para o lojista.
*/

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\CatalogoFiltroRequest;
use App\Services\Portal\CatalogoRevendedorService;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CatalogoController extends Controller
{
    public function __construct(private readonly CatalogoRevendedorService $catalogo) {}

    /**
     * Tela 2.2 — grade filtrável com o custo B2B, disponibilidade lida do cofre
     * e a ficha do painel lateral (`?ver=SKU`).
     *
     * `?exportar=csv` devolve o mesmo recorte como arquivo: é o botão "Exportar
     * catálogo" da barra de filtros, e por isso divide rota e filtros com a
     * tela — o que se baixa é exatamente o que se está vendo.
     */
    public function index(CatalogoFiltroRequest $request): View|StreamedResponse
    {
        $filtros = $request->filtros();

        if ($request->querExportar()) {
            return $this->catalogo->exportar($filtros);
        }

        return view('portal.catalogo.index', $this->catalogo->montarIndice($filtros));
    }
}
