<?php

/*
[Modulo: app/Http/Controllers/Portal]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
"Vitrine para clientes" do menu: abre a loja do proprio revendedor, ou a personalizacao quando ela ainda nao foi publicada.
*/

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\ResellerStore;
use App\Support\ResellerScope;
use Illuminate\Http\RedirectResponse;

class VitrineRedirectController extends Controller
{
    /**
     * O item de menu não é uma tela: é a porta para a vitrine white label do
     * próprio lojista, que é pública e mora em `/loja/{slug}`.
     *
     * A loja vem do {@see ResellerScope} — sempre a do revendedor autenticado,
     * nunca um `{store}` vindo da URL. Um lojista não abre a vitrine de outro
     * por este caminho porque não há por onde informar de quem ela é.
     *
     * Vitrine ainda não publicada não é erro: o lojista é levado à tela onde ele
     * a publica, com o aviso do porquê. Devolver 404 aqui puniria justamente
     * quem ainda está montando a loja.
     */
    public function __invoke(ResellerScope $escopo): RedirectResponse
    {
        $loja = $escopo->store();

        if ($this->publicada($loja)) {
            return redirect()->route('vitrine.index', $loja);
        }

        return redirect()
            ->route('portal.loja.edit')
            ->with('status', 'Sua vitrine ainda não foi publicada. Conclua a personalização e ative a loja para abri-la com o link público.');
    }

    /**
     * Publicada é loja ativa, com data de publicação e com slug — sem slug não
     * há URL de vitrine para montar.
     */
    private function publicada(?ResellerStore $loja): bool
    {
        return $loja !== null
            && (bool) $loja->getAttribute('is_active')
            && $loja->getAttribute('published_at') !== null
            && trim((string) $loja->getAttribute('slug')) !== '';
    }
}
