<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
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
            'customer_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:1000'],
        ];
    }

    /**
     * The validated customer details, shaped for the checkout service.
     *
     * @return array{customer_name: string, email: string, phone: string, address: string}
     */
    public function customerDetails(): array
    {
        return [
            'customer_name' => $this->string('customer_name')->value(),
            'email' => $this->string('email')->value(),
            'phone' => $this->string('phone')->value(),
            'address' => $this->string('address')->value(),
        ];
    }
}
