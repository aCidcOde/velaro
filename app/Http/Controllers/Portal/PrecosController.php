<?php

/*
[Modulo: app/Http/Controllers/Portal]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Tela 2.7: margens do lojista sobre o custo Velaro, preco sugerido por produto e regras de excecao.
*/

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\PrecosFiltroRequest;
use App\Http\Requests\Portal\PrecosUpdateRequest;
use App\Services\Portal\ResellerPricingService;
use App\Support\ResellerScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Preços e margens.
 *
 * É aqui que o lojista **vê o custo Velaro** — `products.price` é o preço B2B
 * que ele paga, e esconder isso dele tiraria o sentido da tela. O que o escopo
 * protege é o outro lado: a margem que ele pratica não pode ser lida por
 * concorrente nenhum, e por isso a configuração e as regras saem sempre de
 * {@see ResellerScope}.
 */
class PrecosController extends Controller
{
    public function __construct(
        private readonly ResellerScope $escopo,
        private readonly ResellerPricingService $precos,
    ) {}

    public function edit(PrecosFiltroRequest $request): View
    {
        return view('portal.precos.edit', $this->precos->montarTela($this->escopo, $request->filtros()) + [
            'acaoSalvar' => PrecosUpdateRequest::ACTION_SAVE,
            'acaoRecalcular' => PrecosUpdateRequest::ACTION_RECALCULATE,
            'acaoAplicar' => PrecosUpdateRequest::ACTION_APPLY_ALL,
        ]);
    }

    public function update(PrecosUpdateRequest $request): RedirectResponse
    {
        $this->precos->atualizar($this->escopo, $request->dados(), $request->querRecalcular());

        return redirect()
            ->route('portal.precos.edit')
            ->with('status', match (true) {
                $request->querAplicarATodos() => 'Margens aplicadas a todos os produtos do catálogo.',
                $request->querRecalcular() => 'Preços recalculados com as margens atuais.',
                default => 'Configurações de preço salvas.',
            });
    }
}
