<?php

/*
[App/Http/Requests/Api/Mobile/Auth]
@Author: André Gomes ( @acidcode )
@since 2026-02-09
Valida payload de cadastro da API mobile.
*/

namespace App\Http\Requests\Api\Mobile\Auth;

use App\Models\User;
use App\Support\DocumentInput;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
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
            'email' => ['required', 'email', 'max:255', Rule::unique(User::class)],
            'phone' => ['required', 'string', 'max:20'],
            'document' => DocumentInput::documentRules(),
            'password' => ['required', 'string', 'confirmed', Password::default()],
            'password_confirmation' => ['required', 'string', 'min:8'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'document' => DocumentInput::normalize($this->input('document')),
        ]);
    }
}
