<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
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
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'regex:/^(?:\+233|0)[235]\d{8}$/'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'delivery_address' => ['required', 'string', 'max:500'],
            'delivery_city' => ['required', 'string', 'max:120'],
            'delivery_region' => ['nullable', 'string', 'max:120'],
            'delivery_zone' => ['required', 'string', Rule::in(array_keys(config('delivery.zones')))],
            'delivery_phone' => ['nullable', 'string', 'regex:/^(?:\+233|0)[235]\d{8}$/'],
            'delivery_notes' => ['nullable', 'string', 'max:1000'],
            'payment_method' => ['required', 'string', 'in:paystack'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'customer_phone.regex' => 'Enter a Ghana mobile number such as 0244000000 or +233244000000.',
            'delivery_phone.regex' => 'Enter a Ghana mobile number such as 0244000000 or +233244000000.',
        ];
    }
}
