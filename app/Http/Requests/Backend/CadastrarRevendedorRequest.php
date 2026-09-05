<?php

/*
[Modulo: app/Http/Requests/Backend]
@Author: André Gomes ( @acidcode )
@since 2026-09-06
Valida o cadastro manual de revendedor feito pelo Painel Interno.
*/

namespace App\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;

class CadastrarRevendedorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasBackendPermission('velaro.resellers.create') === true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            // Os obrigatorios sao os marcados com * na secao 5 do doc da tela.
            'trade_name' => ['required', 'string', 'max:255'],
            'legal_name' => ['required', 'string', 'max:255'],
            // CNPJ e uma das poucas colunas que ficaram em portugues; unico na base.
            'cnpj' => ['required', 'string', 'max:20', 'unique:resellers,cnpj'],
            'state_registration' => ['nullable', 'string', 'max:30'],
            'contact_name' => ['required', 'string', 'max:255'],
            'contact_cpf' => ['nullable', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'postal_code' => ['required', 'string', 'max:12'],
            'street' => ['required', 'string', 'max:255'],
            'street_number' => ['required', 'string', 'max:30'],
            'address_complement' => ['nullable', 'string', 'max:255'],
            'district' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'size:2'],
            'internal_notes' => ['nullable', 'string', 'max:500'],
            'cnaes' => ['array'],
            'cnaes.*.code' => ['required_with:cnaes', 'string', 'max:20'],
            'cnaes.*.description' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cnpj.unique' => 'Já existe um revendedor cadastrado com este CNPJ.',
            'state.size' => 'Use a sigla da UF, com dois caracteres.',
        ];
    }
}
