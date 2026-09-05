<?php

/*
[Modulo: app/Http/Requests/Portal]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Valida a aba e a pagina da tela 2.4 do Portal: lote atual, todos os pedidos ou lotes anteriores.
*/

namespace App\Http\Requests\Portal;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FinanceiroFiltroRequest extends FormRequest
{
    /** As tres abas do prototipo, na ordem em que aparecem. */
    public const ABA_LOTE_ATUAL = 'lote-atual';

    public const ABA_TODOS = 'todos';

    public const ABA_LOTES = 'lotes';

    /** @var list<string> */
    public const ABAS = [self::ABA_LOTE_ATUAL, self::ABA_TODOS, self::ABA_LOTES];

    /** Teto de pagina: `?page=999999` viraria um OFFSET gigante no MySQL. */
    private const PAGINA_MAXIMA = 10000;

    /**
     * Quem entra aqui ja passou por `auth`, `not_blocked`, `verified` e
     * `reseller` — o middleware do grupo `portal.` responde 403 antes do
     * controller para quem nao e lojista aprovado. Repetir a checagem no
     * `authorize()` so criaria um segundo lugar para a regra divergir.
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
            'aba' => ['nullable', 'string', Rule::in(self::ABAS)],
            'page' => ['nullable', 'integer', 'min:1', 'max:'.self::PAGINA_MAXIMA],
        ];
    }

    /**
     * A aba pedida, ja reduzida a uma das tres. Link velho ou aba inventada cai
     * no padrao em vez de derrubar a tela: `?aba=qualquer-coisa` mostra o lote
     * atual, que e o que a tela abre.
     */
    public function aba(): string
    {
        $aba = $this->input('aba');

        return is_string($aba) && in_array($aba, self::ABAS, true) ? $aba : self::ABA_LOTE_ATUAL;
    }

    /**
     * A pagina, presa a faixa util.
     *
     * O valor e **passado ao paginador**, e nao deixado para ele resolver
     * sozinho: `Paginator::resolveCurrentPage()` le a request do container, e nao
     * esta instancia — o que `prepareForValidation()` normaliza aqui nao chega la.
     * Sem isso, `?page=999999999` viraria um OFFSET de um bilhao no MySQL.
     */
    public function pagina(): int
    {
        $pagina = $this->input('page');

        return is_numeric($pagina) ? max(1, min(self::PAGINA_MAXIMA, (int) $pagina)) : 1;
    }

    /**
     * Normaliza antes de validar: aba desconhecida vira ausencia de aba e pagina
     * fora da faixa e podada no proprio input — o paginador le `page` direto da
     * request, entao validar sozinho nao o protegeria.
     */
    protected function prepareForValidation(): void
    {
        $query = $this->query->all();
        $limpo = [];

        if (array_key_exists('aba', $query)) {
            $aba = $query['aba'];
            $limpo['aba'] = is_string($aba) && in_array($aba, self::ABAS, true) ? $aba : null;
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
}
