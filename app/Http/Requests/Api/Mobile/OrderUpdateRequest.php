<?php

namespace App\Http\Requests\Api\Mobile;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrderUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'reference' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'string', Rule::in(['draft', 'submitted', 'processing', 'completed', 'canceled'])],
            'notes' => ['nullable', 'string', 'max:5000'],
            'meta' => ['nullable', 'array'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.status' => ['sometimes', 'string', Rule::in(['pending', 'processing', 'completed'])],
            'items.*.meta' => ['nullable', 'array'],
        ];
    }
}
