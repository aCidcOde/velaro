<?php

namespace App\Http\Requests\Backend;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class OrderItemStatusUpdateRequest extends FormRequest
{
    private const array STATUS_OPTIONS = [
        'pending',
        'processing',
        'awaiting_download',
        'issued',
        'error',
        'canceled',
    ];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('access-backend') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:'.implode(',', self::STATUS_OPTIONS)],
        ];
    }
}
