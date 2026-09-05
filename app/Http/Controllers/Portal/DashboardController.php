<?php

/*
[Modulo: app/Http/Controllers/Portal]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Painel do lojista: um endereco que muda de conteudo conforme o estagio da jornada do revendedor.
*/

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\Portal\JornadaDoLojistaService;
use App\Services\Portal\PainelLojistaService;
use App\Support\EstagioDoLojista;
use App\Support\ResellerScope;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Tela 2.1 e o primeiro passo da jornada, no mesmo endereco.
 *
 * O lojista tem um login e um painel. Aprovado, ele ve o dashboard de sempre —
 * indicadores, pedidos, pendencias, vitrine —, montado pelo
 * {@see PainelLojistaService} a partir do {@see ResellerScope}. Antes disso, o
 * painel mostra o estagio em que ele esta: o acompanhamento da solicitacao, o
 * reenvio de documentos quando a equipe pede, ou o motivo e o caminho de volta
 * quando o cadastro foi recusado ou inativado.
 *
 * Esta rota — e so ela — usa o middleware `reseller.linked`, que pede apenas o
 * vinculo. As outras 18 do portal continuam exigindo revendedor aprovado.
 */
class DashboardController extends Controller
{
    /**
     * Os dois services chegam por injecao normal: nenhum dos dois consulta o
     * banco no construtor, entao montar o que a jornada nao usa nao custa query.
     * Resolver {@see PainelLojistaService} passa pelo {@see ResellerScope}, que
     * exige um usuario vinculado a revendedor — a garantia que o middleware
     * `reseller.linked` acabou de dar.
     */
    public function __invoke(
        Request $request,
        JornadaDoLojistaService $jornada,
        PainelLojistaService $painel,
    ): View {
        $reseller = $request->user()?->reseller;

        if ($reseller !== null && ! EstagioDoLojista::de($reseller)->aprovado()) {
            return view('portal.jornada', $jornada->montar($reseller));
        }

        // Estagio aprovado: o dashboard de sempre, intocado.
        return view('portal.dashboard', $painel->montar());
    }
}
