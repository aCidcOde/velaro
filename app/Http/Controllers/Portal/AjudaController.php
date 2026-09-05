<?php

/*
[Modulo: app/Http/Controllers/Portal]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Central de ajuda do portal: biblioteca publicada, FAQ operacional da plataforma e canais de atendimento.
*/

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\AjudaBuscaRequest;
use App\Services\Portal\CentralAjudaService;
use Illuminate\View\View;

class AjudaController extends Controller
{
    /**
     * A central é documentação da plataforma, não dado do lojista: nada aqui é
     * escopado por `reseller_id` porque nada aqui pertence a um revendedor. O
     * que o middleware `reseller` garante é o público — o consumidor final não
     * tem login e não chega a esta página.
     */
    public function __invoke(AjudaBuscaRequest $request, CentralAjudaService $ajuda): View
    {
        return view('portal.ajuda', $ajuda->montar($request->termo()));
    }
}
