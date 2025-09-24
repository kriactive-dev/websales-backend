<?php

namespace App\Http\Requests\Invoice;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Ajuste conforme suas necessidades de autorização
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'paid_amount' => 'required|numeric|min:0',
            'payment_date' => 'nullable|date',
            'notes' => 'nullable|string|max:1000'
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'paid_amount.required' => 'O valor pago é obrigatório.',
            'paid_amount.numeric' => 'O valor pago deve ser um número.',
            'paid_amount.min' => 'O valor pago deve ser maior ou igual a zero.',
            
            'payment_date.date' => 'A data de pagamento deve ser uma data válida.',
            
            'notes.string' => 'As notas devem ser uma string.',
            'notes.max' => 'As notas não podem exceder 1000 caracteres.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'paid_amount' => 'valor pago',
            'payment_date' => 'data de pagamento',
            'notes' => 'notas',
        ];
    }
}