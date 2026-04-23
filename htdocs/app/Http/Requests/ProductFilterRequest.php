<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductFilterRequest extends FormRequest
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
            'q'           => ['nullable', 'string', 'max:255'],
            'price_from'  => ['nullable', 'numeric', 'min:0'],
            'price_to'    => ['nullable', 'numeric', 'min:0', Rule::when($this->filled('price_from'), 'gte:price_from')],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'in_stock'    => ['nullable', 'boolean'],
            'rating_from' => ['nullable', 'numeric', 'between:0,5'],
            'sort'        => ['nullable', 'in:price_asc,price_desc,rating_desc,newest'],
            'per_page'    => ['nullable', 'integer', 'between:1,100'],
        ];
    }
}
