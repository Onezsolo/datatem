<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:shop_products,id'],
            'variant_id' => ['nullable', 'integer', 'exists:shop_product_variants,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
        ];
    }
}
