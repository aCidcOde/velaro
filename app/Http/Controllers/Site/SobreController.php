<?php

/*
[Modulo: app/Http/Controllers/Site]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Tela 1.2: pagina institucional, montada com o conteudo do grupo `about` de settings.
*/

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\Site\SiteContentService;
use Illuminate\Contracts\View\View;

class SobreController extends Controller
{
    public function __invoke(SiteContentService $conteudo): View
    {
        $sobre = $conteudo->about();

        return view('site.sobre', [
            'texto' => $sobre['texto'],
            'apresentacao' => $sobre['apresentacao'],
            'historia' => $sobre['historia'],
            'diferenciais' => $sobre['diferenciais'],
            'numeros' => $sobre['numeros'],
        ]);
    }
}
