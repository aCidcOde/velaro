<?php

/*
[Modulo: app/Http/Controllers/Portal]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Dashboard do lojista: indicadores, ultimos pedidos, pendencias e configuracao da loja, tudo do proprio revendedor.
*/

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\Portal\PainelLojistaService;
use App\Support\ResellerScope;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Tela 2.1. O controller é fino de propósito: quem monta os números é o
     * service, e ele os monta a partir do {@see ResellerScope} —
     * não há aqui uma query que alguém possa esquecer de filtrar por
     * `reseller_id`.
     */
    public function __invoke(PainelLojistaService $painel): View
    {
        return view('portal.dashboard', $painel->montar());
    }
}
