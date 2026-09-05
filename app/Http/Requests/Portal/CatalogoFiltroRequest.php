<?php

/*
[Modulo: app/Http/Requests/Portal]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Valida e normaliza os filtros do catalogo revendedor: busca, taxonomia, largura, disponibilidade, ordem e recorte do drawer.
*/

namespace App\Http\Requests\Portal;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Barra de filtros da tela 2.2. É irmã de {@see \App\Http\Requests\Site\CatalogoFiltroRequest},
 * e pela mesma razão: slug inexistente devolve grade vazia, não 422 — quem chega
 * por link velho ou por um `select` que apontava para uma coleção desativada tem
 * de receber a tela, não um erro de formulário.
 *
 * O que esta versão acrescenta à do site é o que só o lojista tem: o filtro de
 * **disponibilidade** (que lê `stock_items.available`), a **ordenação** e o
 * recorte de exportação. Nada aqui carrega `reseller_id`: o catálogo é o da
 * fábrica, igual para todo lojista — o que é exclusivo do portal é o custo B2B
 * que a tela exibe, e ele vem de `products.price`, não da query string.
 */
class CatalogoFiltroRequest extends FormRequest
{
    /** Disponibilidade: em estoque, sob encomenda e sem saldo em cofre. */
    public const DISPONIBILIDADE_ESTOQUE = 'estoque';

    public const DISPONIBILIDADE_ENCOMENDA = 'encomenda';

    public const DISPONIBILIDADE_ESGOTADO = 'esgotado';

    /** Ordenação: o protótipo abre em "Lançamento". */
    public const ORDEM_LANCAMENTO = 'lancamento';

    public const ORDEM_NOME = 'nome';

    public const ORDEM_CUSTO_ASC = 'custo_asc';

    public const ORDEM_CUSTO_DESC = 'custo_desc';

    /**
     * @var list<string>
     */
    public const DISPONIBILIDADES = [
        self::DISPONIBILIDADE_ESTOQUE,
        self::DISPONIBILIDADE_ENCOMENDA,
        self::DISPONIBILIDADE_ESGOTADO,
    ];

    /**
     * @var list<string>
     */
    public const ORDENS = [
        self::ORDEM_LANCAMENTO,
        self::ORDEM_NOME,
        self::ORDEM_CUSTO_ASC,
        self::ORDEM_CUSTO_DESC,
    ];

    /**
     * Campos de texto aceitos na query string, na ordem da barra de filtros.
     *
     * @var list<string>
     */
    private const CAMPOS = ['q', 'colecao', 'material', 'acabamento', 'disponibilidade', 'ordenar', 'ver'];

    /**
     * Teto de cada campo de texto. Valor maior é podado, não recusado.
     *
     * @var array<string, int>
     */
    private const LIMITES = ['q' => 120, 'colecao' => 80, 'material' => 80, 'acabamento' => 80, 'ver' => 60];

    /** Teto de página: `?page=999999` viraria um OFFSET gigante no banco. */
    private const PAGINA_MAXIMA = 10000;

    /** Teto da largura em milímetros, o mesmo da regra `between`. */
    private const LARGURA_MAXIMA = 99.99;

    /**
     * O acesso já foi decidido antes do controller: `auth` + `not_blocked` +
     * `verified` + `reseller` no grupo `portal.`. Repetir a checagem aqui só
     * criaria um segundo lugar para errá-la.
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
            'q' => ['nullable', 'string', 'max:'.self::LIMITES['q']],
            'colecao' => ['nullable', 'string', 'max:'.self::LIMITES['colecao']],
            'material' => ['nullable', 'string', 'max:'.self::LIMITES['material']],
            'acabamento' => ['nullable', 'string', 'max:'.self::LIMITES['acabamento']],
            'largura' => ['nullable', 'numeric', 'between:0,'.self::LARGURA_MAXIMA],
            'disponibilidade' => ['nullable', Rule::in(self::DISPONIBILIDADES)],
            'ordenar' => ['nullable', Rule::in(self::ORDENS)],
            'ver' => ['nullable', 'string', 'max:'.self::LIMITES['ver']],
            'page' => ['nullable', 'integer', 'min:1', 'max:'.self::PAGINA_MAXIMA],
        ];
    }

    /**
     * Filtros já normalizados, prontos para o service.
     *
     * @return array{q: string|null, colecao: string|null, material: string|null, acabamento: string|null, largura: float|null, disponibilidade: string|null, ordenar: string, ver: string|null}
     */
    public function filtros(): array
    {
        $largura = $this->input('largura');
        $ordenar = $this->textoOuNulo('ordenar');

        return [
            'q' => $this->textoOuNulo('q'),
            'colecao' => $this->textoOuNulo('colecao'),
            'material' => $this->textoOuNulo('material'),
            'acabamento' => $this->textoOuNulo('acabamento'),
            'largura' => is_numeric($largura) ? (float) $largura : null,
            'disponibilidade' => $this->textoOuNulo('disponibilidade'),
            // A tela abre ordenada por lançamento, como o protótipo.
            'ordenar' => $ordenar ?? self::ORDEM_LANCAMENTO,
            'ver' => $this->textoOuNulo('ver'),
        ];
    }

    /**
     * `?exportar=csv` devolve a mesma seleção como arquivo, em vez de HTML.
     * É o botão "Exportar catálogo" da barra de filtros — o recorte exportado é
     * exatamente o que está na tela, com os mesmos filtros aplicados.
     */
    public function querExportar(): bool
    {
        return $this->query('exportar') === 'csv';
    }

    /**
     * Mesma política do catálogo público: em vez de reprovar, esta etapa
     * conserta. Texto longo é podado, número mal formado vira ausência de
     * filtro, e opção fora da lista (`?ordenar=preco`) volta ao padrão. Um link
     * antigo do lojista precisa abrir a tela, não um redirect de validação.
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

        // `largura` não está em CAMPOS (não é texto): vem crua da query string.
        $largura = $query['largura'] ?? null;

        // Fora da faixa de uma aliança (`?largura=500`) é ausência de filtro, e
        // não motivo para recusar a página.
        if ($largura !== null && (! is_numeric($largura) || (float) $largura < 0 || (float) $largura > self::LARGURA_MAXIMA)) {
            $limpo['largura'] = null;
        }

        foreach (['disponibilidade' => self::DISPONIBILIDADES, 'ordenar' => self::ORDENS] as $campo => $aceitos) {
            $valor = $limpo[$campo] ?? null;

            if ($valor !== null && ! in_array($valor, $aceitos, true)) {
                $limpo[$campo] = null;
            }
        }

        if (array_key_exists('page', $query)) {
            $pagina = $query['page'];
            // O paginador lê `page` direto da request: validar sozinho não o
            // protegeria, a poda precisa acontecer no próprio input.
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
