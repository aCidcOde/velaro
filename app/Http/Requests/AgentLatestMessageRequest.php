<?php

/*
app/Http/Requests
@Author: André Gomes ( @acidcode )
@since 2026-02-03
Validacao do polling do agente para ultima mensagem.
*/

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AgentLatestMessageRequest extends FormRequest
{
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
            'after' => ['nullable', 'integer', 'min:1'],
            'conversation_id' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'after.integer' => __('Parametro invalido.'),
            'after.min' => __('Parametro invalido.'),
            'conversation_id.integer' => __('Conversa invalida.'),
            'conversation_id.min' => __('Conversa invalida.'),
        ];
    }
}
