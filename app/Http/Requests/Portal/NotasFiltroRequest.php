<?php

/*
[Modulo: app/Http/Requests/Portal]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Valida a barra de filtros das notas fiscais do lojista: busca, periodo, competencia, status e serie.
*/

namespace App\Http\Requests\Portal;

use App\Models\Invoice;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NotasFiltroRequest extends FormRequest
{
    /** Abas do prototipo: todas, so as autorizadas, so as canceladas. */
    public const ABA_TODAS = 'todas';

    /** @var array<string, string> */
    public const ABAS = [
        self::ABA_TODAS => 'Todas as notas',
        Invoice::STATUS_AUTHORIZED => 'Autorizadas',
        Invoice::STATUS_CANCELED => 'Canceladas',
    ];

    /**
     * Janelas do seletor "Período", em dias. `0` e "todo o historico".
     *
     * As chaves numericas viram `int` — o PHP converte chave numerica de array —,
     * entao a comparacao estrita com o valor da query string (sempre `string`)
     * usa {@see PERIODO_VALORES}, e nao `array_keys()`.
     *
     * @var array<int, string>
     */
    public const PERIODOS = [
        30 => 'Últimos 30 dias',
        90 => 'Últimos 90 dias',
        365 => 'Últimos 12 meses',
        0 => 'Todo o período',
    ];

    /** @var list<string> */
    public const PERIODO_VALORES = ['30', '90', '365', '0'];

    /** Tamanhos de pagina oferecidos no rodape da tabela. */
    public const POR_PAGINA = [6, 12, 24, 48];

    /** Janela e tamanho de pagina que a tela abre — e que por isso nao entram na URL. */
    public const PERIODO_PADRAO = '90';

    public const POR_PAGINA_PADRAO = 6;

    private const BUSCA_MAXIMA = 60;

    private const SERIE_MAXIMA = 10;

    private const PAGINA_MAXIMA = 10000;

    /**
     * Ver a nota de {@see FinanceiroFiltroRequest::authorize()}: o middleware
     * `reseller` ja barrou quem nao e lojista aprovado.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:'.self::BUSCA_MAXIMA],
            'aba' => ['nullable', 'string', Rule::in(array_keys(self::ABAS))],
            'periodo' => ['nullable', 'string', Rule::in(self::PERIODO_VALORES)],
            // Competencia e um mes: `2026-05`. Mes inexistente nao encontra nota.
            'competencia' => ['nullable', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'serie' => ['nullable', 'string', 'max:'.self::SERIE_MAXIMA],
            'por_pagina' => ['nullable', 'integer', Rule::in(self::POR_PAGINA)],
            'page' => ['nullable', 'integer', 'min:1', 'max:'.self::PAGINA_MAXIMA],
        ];
    }

    /**
     * Filtros normalizados, prontos para o service.
     *
     * @return array{q: string|null, aba: string, periodo: string, competencia: string|null, serie: string|null, por_pagina: int, pagina: int}
     */
    public function filtros(): array
    {
        return [
            'q' => $this->textoOuNulo('q'),
            'aba' => $this->umDentre('aba', array_keys(self::ABAS), self::ABA_TODAS),
            'periodo' => $this->umDentre('periodo', self::PERIODO_VALORES, self::PERIODO_PADRAO),
            'competencia' => $this->textoOuNulo('competencia'),
            'serie' => $this->textoOuNulo('serie'),
            'por_pagina' => $this->porPagina(),
            'pagina' => $this->pagina(),
        ];
    }

    /**
     * Ver a nota de {@see FinanceiroFiltroRequest::pagina()}: o paginador le a
     * request do container, nao esta instancia, entao a pagina vai podada de ca.
     */
    public function pagina(): int
    {
        $pagina = $this->input('page');

        return is_numeric($pagina) ? max(1, min(self::PAGINA_MAXIMA, (int) $pagina)) : 1;
    }

    /**
     * Mesma politica do catalogo publico: valor torto vira ausencia de filtro, e
     * nao um 422 — a tela e um relatorio consultado por link salvo e por
     * favorito, e recusar a pagina inteira por causa de `?serie=` seria pior que
     * devolver a lista completa.
     */
    protected function prepareForValidation(): void
    {
        $query = $this->query->all();
        $limpo = [];

        foreach (['q', 'aba', 'periodo', 'competencia', 'serie'] as $campo) {
            if (! array_key_exists($campo, $query)) {
                continue;
            }

            $valor = $query[$campo];
            $limpo[$campo] = is_string($valor) && trim($valor) !== '' ? trim($valor) : null;
        }

        foreach (['q' => self::BUSCA_MAXIMA, 'serie' => self::SERIE_MAXIMA] as $campo => $limite) {
            if (is_string($limpo[$campo] ?? null)) {
                $limpo[$campo] = mb_substr((string) $limpo[$campo], 0, $limite);
            }
        }

        foreach (['aba' => array_keys(self::ABAS), 'periodo' => self::PERIODO_VALORES] as $campo => $aceitos) {
            if (array_key_exists($campo, $limpo) && ! in_array($limpo[$campo], $aceitos, true)) {
                $limpo[$campo] = null;
            }
        }

        if (array_key_exists('competencia', $limpo)
            && is_string($limpo['competencia'])
            && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $limpo['competencia']) !== 1) {
            $limpo['competencia'] = null;
        }

        if (array_key_exists('por_pagina', $query)) {
            $valor = $query['por_pagina'];
            $limpo['por_pagina'] = is_numeric($valor) && in_array((int) $valor, self::POR_PAGINA, true)
                ? (string) (int) $valor
                : (string) self::POR_PAGINA_PADRAO;
        }

        if (array_key_exists('page', $query)) {
            $pagina = $query['page'];
            $limpo['page'] = is_numeric($pagina)
                ? (string) max(1, min(self::PAGINA_MAXIMA, (int) $pagina))
                : '1';
        }

        if ($limpo !== []) {
            $this->merge($limpo);
        }
    }

    private function porPagina(): int
    {
        $valor = $this->input('por_pagina');

        return is_numeric($valor) && in_array((int) $valor, self::POR_PAGINA, true)
            ? (int) $valor
            : self::POR_PAGINA_PADRAO;
    }

    /**
     * @param  list<string>  $aceitos
     */
    private function umDentre(string $campo, array $aceitos, string $padrao): string
    {
        $valor = $this->input($campo);

        return is_string($valor) && in_array($valor, $aceitos, true) ? $valor : $padrao;
    }

    private function textoOuNulo(string $campo): ?string
    {
        $valor = $this->input($campo);

        return is_string($valor) && $valor !== '' ? $valor : null;
    }
}
