<?php

namespace App\Http\Requests\Invoice;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceItemRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:1',
            'discount' => 'nullable|integer|min:0|max:100',
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
            'name.required' => 'O nome do item é obrigatório.',
            'name.string' => 'O nome do item deve ser uma string.',
            'name.max' => 'O nome do item não pode exceder 255 caracteres.',
            
            'description.string' => 'A descrição deve ser uma string.',
            'description.max' => 'A descrição não pode exceder 500 caracteres.',
            
            'price.required' => 'O preço do item é obrigatório.',
            'price.numeric' => 'O preço deve ser um valor numérico.',
            'price.min' => 'O preço deve ser maior ou igual a zero.',
            
            'quantity.required' => 'A quantidade do item é obrigatória.',
            'quantity.integer' => 'A quantidade deve ser um número inteiro.',
            'quantity.min' => 'A quantidade deve ser pelo menos 1.',
            
            'discount.integer' => 'O desconto deve ser um número inteiro.',
            'discount.min' => 'O desconto deve ser maior ou igual a 0.',
            'discount.max' => 'O desconto não pode ser maior que 100%.',
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
            'name' => 'nome do item',
            'description' => 'descrição do item',
            'price' => 'preço do item',
            'quantity' => 'quantidade do item',
            'discount' => 'desconto do item',
        ];
    }
}