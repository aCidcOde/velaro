<?php

/*
[Modulo: app/Http/Requests/Portal]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Valida o termo da busca da central de ajuda: texto curto, opcional e sempre normalizado.
*/

namespace App\Http\Requests\Portal;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AjudaBuscaRequest extends FormRequest
{
    /** Teto do termo; acima disso o valor é podado, não recusado. */
    private const LIMITE = 120;

    /**
     * O acesso já foi decidido pelo grupo `portal.` (`auth` + `not_blocked` +
     * `verified` + `reseller`).
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
            'q' => ['nullable', 'string', 'max:'.self::LIMITE],
        ];
    }

    /**
     * Termo normalizado, ou `null` quando a busca não foi usada.
     */
    public function termo(): ?string
    {
        $termo = $this->input('q');

        return is_string($termo) && $termo !== '' ? $termo : null;
    }

    /**
     * Busca vazia é ausência de busca, e termo longo é podado: a central é uma
     * página de leitura, e um 422 aqui só tiraria a documentação do ar para
     * quem digitou demais.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->query->has('q')) {
            return;
        }

        $termo = $this->query->get('q');
        $termo = is_string($termo) ? trim($termo) : '';

        $this->merge(['q' => $termo !== '' ? mb_substr($termo, 0, self::LIMITE) : null]);
    }
}
