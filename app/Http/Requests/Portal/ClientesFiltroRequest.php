<?php

/*
[Modulo: app/Http/Requests/Portal]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Valida e normaliza os filtros da carteira de clientes do portal: busca, situacao, cidade/UF e periodo do cadastro.
*/

namespace App\Http\Requests\Portal;

use App\Support\ResellerScope;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Barra de filtros da tela 2.3 (31-portal-clientes.html).
 *
 * Como em {@see PedidosFiltroRequest}, nenhuma regra consulta o banco: um
 * `exists` em `customers` responderia sobre a carteira de todos os revendedores,
 * e a busca por CPF é justamente o campo em que isso importaria — confirmar que
 * um documento existe na base já é informação sobre o cliente de outro lojista.
 * Quem sabe o que existe é {@see ResellerScope}, um degrau adiante.
 */
class ClientesFiltroRequest extends FormRequest
{
    /** Janelas do select "Período do cadastro", em dias. `0` é "Todas". */
    public const PERIODOS = [30, 90, 180, 365, 0];

    /** O protótipo abre a tela com o período em "Todas". */
    public const PERIODO_PADRAO = 0;

    public const SITUACOES = ['ativo', 'inativo'];

    private const PAGINA_MAXIMA = 10000;

    /**
     * A carteira é a do revendedor autenticado; o middleware `reseller` já
     * decidiu o acesso ao ambiente.
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
            'q' => ['nullable', 'string', 'max:120'],
            'situacao' => ['nullable', Rule::in(self::SITUACOES)],
            // "Cidade / UF" é um select só no protótipo; o valor viaja com as
            // duas partes ("São Paulo|SP") porque cidade homônima em estados
            // diferentes existe e filtrar só pelo nome misturaria as duas.
            'local' => ['nullable', 'string', 'max:120'],
            'periodo' => ['nullable', Rule::in(self::PERIODOS)],
            'page' => ['nullable', 'integer', 'min:1', 'max:'.self::PAGINA_MAXIMA],
        ];
    }

    /**
     * @return array{q: string|null, situacao: string|null, cidade: string|null, uf: string|null, local: string|null, periodo: int}
     */
    public function filtros(): array
    {
        $periodo = $this->input('periodo');
        $local = $this->textoOuNulo('local');
        [$cidade, $uf] = $this->partesDoLocal($local);

        return [
            'q' => $this->textoOuNulo('q'),
            'situacao' => $this->textoOuNulo('situacao'),
            'cidade' => $cidade,
            'uf' => $uf,
            'local' => $local,
            'periodo' => is_numeric($periodo) ? (int) $periodo : self::PERIODO_PADRAO,
        ];
    }

    /**
     * @return array{0: string|null, 1: string|null}
     */
    private function partesDoLocal(?string $local): array
    {
        if ($local === null) {
            return [null, null];
        }

        $partes = explode('|', $local, 2);
        $cidade = trim($partes[0]);
        $uf = trim($partes[1] ?? '');

        return [$cidade === '' ? null : $cidade, $uf === '' ? null : mb_strtoupper($uf)];
    }

    /**
     * Mesma escolha da tela 2.5: valor inválido deixa de filtrar em vez de
     * derrubar a página com 422. A carteira é a tela de trabalho do balcão, e um
     * link antigo com `?situacao=Ativo` precisa abrir a lista, não um erro.
     */
    protected function prepareForValidation(): void
    {
        $query = $this->query->all();
        $limpo = [];

        foreach (['q', 'situacao', 'local'] as $campo) {
            if (! array_key_exists($campo, $query)) {
                continue;
            }

            $valor = $query[$campo];
            $limpo[$campo] = is_string($valor) && trim($valor) !== '' ? mb_substr(trim($valor), 0, 120) : null;
        }

        if (isset($limpo['situacao']) && ! in_array($limpo['situacao'], self::SITUACOES, true)) {
            $limpo['situacao'] = null;
        }

        $periodo = $query['periodo'] ?? null;
        $limpo['periodo'] = is_numeric($periodo) && in_array((int) $periodo, self::PERIODOS, true)
            ? (int) $periodo
            : self::PERIODO_PADRAO;

        if (array_key_exists('page', $query)) {
            $pagina = $query['page'];
            $limpo['page'] = is_numeric($pagina)
                ? (string) max(1, min(self::PAGINA_MAXIMA, (int) $pagina))
                : '1';
        }

        $this->merge($limpo);
    }

    private function textoOuNulo(string $campo): ?string
    {
        $valor = $this->input($campo);

        return is_string($valor) && $valor !== '' ? $valor : null;
    }
}
