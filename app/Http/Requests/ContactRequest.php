<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ContactRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'message' => ['required', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => __('Informe seu nome.'),
            'name.max' => __('O nome pode ter no máximo 255 caracteres.'),
            'email.required' => __('Informe seu e-mail.'),
            'email.email' => __('Informe um e-mail válido.'),
            'email.max' => __('O e-mail pode ter no máximo 255 caracteres.'),
            'phone.required' => __('Informe seu telefone.'),
            'phone.max' => __('O telefone pode ter no máximo 50 caracteres.'),
            'message.required' => __('Escreva sua mensagem.'),
            'message.max' => __('A mensagem pode ter no máximo 2000 caracteres.'),
        ];
    }
}
