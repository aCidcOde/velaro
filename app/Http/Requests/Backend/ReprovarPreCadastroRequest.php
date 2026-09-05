<?php

/*
[Modulo: app/Http/Requests/Backend]
@Author: André Gomes ( @acidcode )
@since 2026-09-06
Valida a justificativa e a permissao para reprovar um pre-cadastro de lojista.
*/

namespace App\Http\Requests\Backend;

use App\Models\Reseller;
use App\Services\Backend\PreCadastroService;
use Illuminate\Foundation\Http\FormRequest;

class ReprovarPreCadastroRequest extends FormRequest
{
    public function authorize(): bool
    {
        $reseller = $this->route('reseller');

        // A decisao so cabe sobre quem ainda esta na fila: um cadastro ja aprovado
        // ou reprovado sai da 3.11 para a 3.10, e decidir de novo por aqui geraria
        // um evento de transicao a partir de um estado que a tela nao mostra mais.
        return $this->user()?->hasBackendPermission('velaro.prospects.reject') === true
            && $reseller instanceof Reseller
            && in_array($reseller->status, PreCadastroService::STATUS_EM_FILA, true);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            // O §7 do Anexo I exige justificativa registrada em acao sensivel: e ela
            // que vira `note` no evento de status e chega ao lojista na tela 1.6.
            'justificativa' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['justificativa' => 'justificativa'];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'justificativa.required' => 'Registre a justificativa da decisão.',
            'justificativa.min' => 'A justificativa precisa de ao menos 10 caracteres.',
        ];
    }
}
