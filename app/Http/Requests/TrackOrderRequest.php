<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TrackOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'order_number' => ['required', 'string', 'max:32'],
            'phone' => ['required', 'string', 'regex:/^(?:\+233|0)[235]\d{8}$/'],
        ];
    }
}
