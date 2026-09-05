<?php

/*
[Modulo: app/Http/Requests/Portal]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Normaliza a barra de filtros e as abas da tabela de precos da tela 2.7.
*/

namespace App\Http\Requests\Portal;

use App\Services\Portal\ResellerPricingService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PrecosFiltroRequest extends FormRequest
{
    /** Teto da busca; `?page=999999` viraria um OFFSET gigante no MySQL. */
    private const PAGINA_MAXIMA = 10000;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Filtro torto não é erro de formulário: a tela abre com a tabela inteira.
     * Um 422 numa tela de leitura tiraria o lojista da página por causa de um
     * link velho no favorito.
     */
    protected function prepareForValidation(): void
    {
        $limpo = [];

        foreach (['q' => 120, 'colecao' => 80, 'material' => 80, 'acabamento' => 80] as $campo => $limite) {
            $valor = $this->query($campo);
            $texto = is_string($valor) ? trim($valor) : '';
            $limpo[$campo] = $texto === '' ? null : mb_substr($texto, 0, $limite);
        }

        $aba = $this->query('aba');
        $limpo['aba'] = is_string($aba) && in_array($aba, ResellerPricingService::TABS, true)
            ? $aba
            : ResellerPricingService::TAB_PRODUCTS;

        $porPagina = $this->query('por_pagina');
        $limpo['por_pagina'] = is_numeric($porPagina) && in_array((int) $porPagina, ResellerPricingService::PER_PAGE_OPTIONS, true)
            ? (int) $porPagina
            : ResellerPricingService::PER_PAGE_DEFAULT;

        $pagina = $this->query('page');

        if ($pagina !== null) {
            // O paginador lê `page` direto da request: podar só na validação não
            // o protegeria.
            $limpo['page'] = is_numeric($pagina)
                ? (string) max(1, min(self::PAGINA_MAXIMA, (int) $pagina))
                : '1';
        }

        $this->merge($limpo);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:120'],
            'colecao' => ['nullable', 'string', 'max:80'],
            'material' => ['nullable', 'string', 'max:80'],
            'acabamento' => ['nullable', 'string', 'max:80'],
            'aba' => ['required', Rule::in(ResellerPricingService::TABS)],
            'por_pagina' => ['required', 'integer', Rule::in(ResellerPricingService::PER_PAGE_OPTIONS)],
            'page' => ['nullable', 'integer', 'min:1', 'max:'.self::PAGINA_MAXIMA],
        ];
    }

    /**
     * @return array{q: string|null, colecao: string|null, material: string|null, acabamento: string|null, aba: string, por_pagina: int}
     */
    public function filtros(): array
    {
        return [
            'q' => $this->textoOuNulo('q'),
            'colecao' => $this->textoOuNulo('colecao'),
            'material' => $this->textoOuNulo('material'),
            'acabamento' => $this->textoOuNulo('acabamento'),
            'aba' => $this->string('aba')->toString(),
            'por_pagina' => (int) $this->input('por_pagina'),
        ];
    }

    private function textoOuNulo(string $campo): ?string
    {
        $valor = $this->input($campo);

        return is_string($valor) && $valor !== '' ? $valor : null;
    }
}
