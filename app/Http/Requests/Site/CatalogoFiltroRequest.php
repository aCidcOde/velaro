<?php

/*
[Modulo: app/Http/Requests/Site]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Valida e normaliza os filtros da vitrine publica do catalogo (busca, colecao, material, acabamento, largura e formato).
*/

namespace App\Http\Requests\Site;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CatalogoFiltroRequest extends FormRequest
{
    /**
     * Campos aceitos na query string da tela 1.3. A ordem e a mesma da barra de
     * filtros do prototipo (11-site-catalogo.html).
     *
     * @var list<string>
     */
    private const CAMPOS = ['q', 'colecao', 'material', 'acabamento', 'largura', 'formato'];

    /**
     * Teto de cada campo de texto, o mesmo das regras. Valor maior e podado em
     * vez de recusado — ver `prepareForValidation()`.
     *
     * @var array<string, int>
     */
    private const LIMITES = ['q' => 120, 'colecao' => 80, 'material' => 80, 'acabamento' => 80, 'formato' => 80];

    /** Teto de pagina: `?page=999999` viraria um OFFSET gigante no MySQL. */
    private const PAGINA_MAXIMA = 10000;

    /** Teto da largura em milimetros, o mesmo da regra `between`. */
    private const LARGURA_MAXIMA = 99.99;

    /**
     * Catalogo publico: sem login, sem permissao.
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
        // Slug invalido nao e erro de formulario: a consulta simplesmente nao
        // encontra nada. Por isso a regra so limita tipo e tamanho, sem `exists`
        // (um 422 numa pagina publica indexavel seria pior que uma lista vazia).
        // `prepareForValidation()` ja normaliza tudo o que chega, entao estas
        // regras sao rede de seguranca: na pratica nenhuma delas reprova.
        return [
            'q' => ['nullable', 'string', 'max:'.self::LIMITES['q']],
            'colecao' => ['nullable', 'string', 'max:'.self::LIMITES['colecao']],
            'material' => ['nullable', 'string', 'max:'.self::LIMITES['material']],
            'acabamento' => ['nullable', 'string', 'max:'.self::LIMITES['acabamento']],
            'largura' => ['nullable', 'numeric', 'between:0,'.self::LARGURA_MAXIMA],
            'formato' => ['nullable', 'string', 'max:'.self::LIMITES['formato']],
            'page' => ['nullable', 'integer', 'min:1', 'max:'.self::PAGINA_MAXIMA],
        ];
    }

    /**
     * Filtros ja normalizados, prontos para o service.
     *
     * @return array{q: string|null, colecao: string|null, material: string|null, acabamento: string|null, largura: float|null, formato: string|null}
     */
    public function filtros(): array
    {
        $largura = $this->input('largura');

        return [
            'q' => $this->textoOuNulo('q'),
            'colecao' => $this->textoOuNulo('colecao'),
            'material' => $this->textoOuNulo('material'),
            'acabamento' => $this->textoOuNulo('acabamento'),
            'largura' => is_numeric($largura) ? (float) $largura : null,
            'formato' => $this->textoOuNulo('formato'),
        ];
    }

    /**
     * A colecao tem rota propria (`/catalogo/{colecao}`), mas o `select` da barra
     * de filtros so consegue mandar query string. Quando ela vem por ali, o
     * controller redireciona para a URL canonica.
     */
    public function informouColecaoNaQuery(): bool
    {
        return array_key_exists('colecao', $this->query->all());
    }

    /**
     * Espaco em branco e campo vazio viram `null` antes da validacao — assim
     * `?material=` nao vira filtro por string vazia. So mexe nas chaves que
     * realmente vieram, para que `informouColecaoNaQuery()` continue confiavel.
     *
     * Alem de limpar, esta etapa **conserta** o que chegou torto: texto longo
     * demais e podado, numero mal formado (`?largura=grossa`, `?page=abc`) vira
     * ausencia de filtro e a pagina e presa na faixa util. O motivo e o mesmo da
     * ausencia de `exists` nas regras: `/catalogo` e publica e indexavel, e um
     * link velho ou um rastreador tem de receber a grade — nao um redirect de
     * erro de validacao, que sem `Referer` joga o visitante na raiz do site.
     */
    protected function prepareForValidation(): void
    {
        $query = $this->query->all();
        $limpo = [];

        foreach (self::CAMPOS as $campo) {
            if (! array_key_exists($campo, $query)) {
                continue;
            }

            $valor = $query[$campo];
            $limpo[$campo] = is_string($valor) && trim($valor) !== '' ? trim($valor) : null;
        }

        foreach (self::LIMITES as $campo => $limite) {
            $valor = $limpo[$campo] ?? null;

            if (is_string($valor)) {
                $limpo[$campo] = mb_substr($valor, 0, $limite);
            }
        }

        $largura = $limpo['largura'] ?? null;

        // Fora da faixa de uma alianca (`?largura=500`) tambem e ausencia de
        // filtro, e nao motivo para recusar a pagina.
        if ($largura !== null && (! is_numeric($largura) || (float) $largura < 0 || (float) $largura > self::LARGURA_MAXIMA)) {
            $limpo['largura'] = null;
        }

        if (array_key_exists('page', $query)) {
            $pagina = $query['page'];
            // O paginador le `page` direto da request, entao a poda precisa
            // acontecer no proprio input — validar sozinho nao o protegeria.
            $limpo['page'] = is_numeric($pagina)
                ? (string) max(1, min(self::PAGINA_MAXIMA, (int) $pagina))
                : '1';
        }

        if ($limpo !== []) {
            $this->merge($limpo);
        }
    }

    private function textoOuNulo(string $campo): ?string
    {
        $valor = $this->input($campo);

        return is_string($valor) && $valor !== '' ? $valor : null;
    }
}
