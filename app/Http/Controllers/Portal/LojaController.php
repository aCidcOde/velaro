<?php

/*
[Modulo: app/Http/Controllers/Portal]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Tela 2.6: identidade da vitrine do lojista, regra de preco do bloco 2 e publicacao da loja.
*/

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\LojaUpdateRequest;
use App\Models\ResellerPriceSetting;
use App\Models\ResellerStore;
use App\Services\Portal\ResellerPricingService;
use App\Services\Portal\StoreProfileService;
use App\Support\ResellerScope;
use App\Support\ValorPtBr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Personalização da loja — a única tela que escreve a pintura da vitrine.
 *
 * O escopo entra pelo construtor: {@see ResellerScope} é `scoped` no container e
 * já chega amarrado ao revendedor autenticado, então não há como este controller
 * alcançar a loja de outro lojista nem por engano.
 */
class LojaController extends Controller
{
    /** Onde a logo e o banner de cada loja ficam, no disco público. */
    private const UPLOAD_DIR = 'vitrines';

    /**
     * Os três toggles do bloco ② — colunas de `reseller_price_settings`, não da
     * loja. O texto é o do protótipo.
     *
     * @var array<string, string>
     */
    private const TOGGLES_DE_PRECO = [
        'apply_to_all' => 'Aplicar a todos os produtos do catálogo',
        'allow_manual_override' => 'Permitir edição manual por produto',
        'allow_promotional_prices' => 'Permitir preços promocionais',
    ];

    public function __construct(
        private readonly ResellerScope $escopo,
        private readonly StoreProfileService $lojas,
        private readonly ResellerPricingService $precos,
    ) {}

    public function edit(): View
    {
        $loja = $this->lojas->loja($this->escopo);
        $configuracao = $this->precos->configuracao($this->escopo);
        $resolvedor = $this->precos->resolvedor($this->escopo);
        $multiplicador = (float) $configuracao->multiplier;

        return view('portal.loja.edit', [
            'loja' => $loja,
            'configuracao' => $configuracao,
            'previa' => $this->lojas->previa($this->escopo, $loja, $resolvedor),
            'paleta' => $this->lojas->estiloDaPaleta($loja),
            'cores' => StoreProfileService::COLORS,
            // Os cinco toggles saem juntos: um checkbox ausente no POST é
            // "desligado", nunca "não mexeu", então nenhum pode ficar de fora.
            'toggles' => StoreProfileService::TOGGLES,
            'togglesDePreco' => self::TOGGLES_DE_PRECO,
            'modelos' => ResellerPricingService::PRICING_MODEL_LABELS,
            'multiplicadorModelo' => ResellerPriceSetting::PRICING_MODEL_MULTIPLIER,
            'fator' => ValorPtBr::multiplicador($multiplicador),
            'exemplos' => $this->lojas->exemplosDeCalculo($resolvedor, '× '.ValorPtBr::numero($multiplicador)),
            'acaoSalvar' => LojaUpdateRequest::ACTION_SAVE,
            'acaoPublicar' => LojaUpdateRequest::ACTION_PUBLISH,
        ]);
    }

    public function update(LojaUpdateRequest $request): RedirectResponse
    {
        $dados = $request->dadosDaLoja();

        foreach (['logo' => 'logo_path', 'banner' => 'banner_path'] as $campo => $coluna) {
            $caminho = $this->guardarImagem($request->file($campo));

            // Sem arquivo novo a coluna não é tocada: um formulário salvo sem
            // reenviar a logo não pode apagar a logo que já está no ar.
            if ($caminho !== null) {
                $dados[$coluna] = $caminho;
            }
        }

        // A tela grava em duas tabelas — `reseller_stores` e
        // `reseller_price_settings` — e o formulário é um só: as duas escritas
        // andam juntas ou nenhuma anda. Meia gravação deixaria a vitrine com a
        // identidade nova e a margem velha.
        $loja = DB::transaction(function () use ($request, $dados): ResellerStore {
            $loja = $this->lojas->atualizar($this->escopo, $dados, $request->querPublicar());

            // O bloco ② da tela é `reseller_price_settings` — a mesma linha da
            // tela 2.7. Gravar pelo service de preço mantém uma verdade só sobre
            // a margem.
            $this->precos->atualizar($this->escopo, $request->dadosDePreco());

            return $loja;
        });

        return redirect()
            ->route('portal.loja.edit')
            ->with('status', $request->querPublicar()
                ? 'Vitrine publicada. Sua loja já está no ar em /loja/'.$loja->slug.'.'
                : 'Configurações da loja salvas.');
    }

    /**
     * Guarda a imagem no disco público e devolve o caminho relativo. Nada
     * enviado devolve nulo, e a coluna fica como estava.
     */
    private function guardarImagem(mixed $arquivo): ?string
    {
        if (! $arquivo instanceof UploadedFile) {
            return null;
        }

        $caminho = $arquivo->store(self::UPLOAD_DIR.'/'.$this->escopo->reseller->getKey(), 'public');

        return is_string($caminho) && $caminho !== '' ? $caminho : null;
    }
}
