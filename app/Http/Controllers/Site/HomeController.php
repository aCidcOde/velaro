<?php

/*
[Modulo: app/Http/Controllers/Site]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Tela 1.1: pagina inicial B2B com a vitrine de colecoes ativas, sem nenhum preco.
*/

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\Site\SiteContentService;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(SiteContentService $conteudo): View
    {
        // So a colecao entra na resposta: `products.price` e custo B2B e nao
        // e lido aqui nem serializado em JSON embutido (regra 2 do escopo 1.1).
        return view('site.home', [
            'colecoes' => $conteudo->activeCollections(),
        ]);
    }
}
