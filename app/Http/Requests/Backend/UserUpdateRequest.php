<?php

namespace App\Http\Requests\Backend;

use App\Models\User;
use App\Support\DocumentInput;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User && $user->hasBackendPermission('backend.users.update');
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
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->route('user')),
            ],
            'phone' => ['nullable', 'string', 'max:50'],
            'document' => DocumentInput::documentRules(required: false),
            'is_admin' => ['nullable', 'boolean'],
            'is_agent' => ['nullable', 'boolean'],
            'is_blocked' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'document' => DocumentInput::normalize($this->input('document')),
        ]);
    }
}
