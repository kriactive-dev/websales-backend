<?php

namespace App\Http\Requests\Invoice;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
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
            // Dados do cliente
            'client_name' => 'required|string|max:255',
            'client_address' => 'nullable|string|max:500',
            'client_phone_number' => 'nullable|string|max:20',
            'client_nuit' => 'nullable|string|max:20',
            
            // Dados da fatura
            'invoice_number' => 'required|string|unique:invoices,invoice_number|max:255',
            'invoice_type' => 'nullable|string|in:Factura,Proforma,Orçamento|max:255',
            'invoice_operation_date' => 'required|date',
            'invoice_payment_date' => 'nullable|date|after_or_equal:invoice_operation_date',
            'invoice_notes' => 'nullable|string|max:1000',
            
            // Dados do sistema
            'system_user' => 'nullable|string|max:255',
            'system_attendant' => 'nullable|string|max:255',
            
            // Valores adicionais da fatura
            'invoice_discount_amount' => 'nullable|numeric|min:0',
            'invoice_transshipment_amount' => 'nullable|numeric|min:0',
            'invoice_taxes_amount' => 'nullable|numeric|min:0',
            
            // Itens da fatura
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string|max:255',
            'items.*.description' => 'nullable|string|max:500',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.discount' => 'nullable|integer|min:0|max:100',
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
            'client_name.required' => 'O nome do cliente é obrigatório.',
            'client_name.string' => 'O nome do cliente deve ser uma string.',
            'client_name.max' => 'O nome do cliente não pode exceder 255 caracteres.',
            
            'invoice_number.required' => 'O número da fatura é obrigatório.',
            'invoice_number.unique' => 'Este número de fatura já existe.',
            'invoice_number.max' => 'O número da fatura não pode exceder 255 caracteres.',
            
            'invoice_type.in' => 'O tipo de fatura deve ser: Factura, Proforma ou Orçamento.',
            
            'invoice_operation_date.required' => 'A data de operação é obrigatória.',
            'invoice_operation_date.date' => 'A data de operação deve ser uma data válida.',
            
            'invoice_payment_date.date' => 'A data de pagamento deve ser uma data válida.',
            'invoice_payment_date.after_or_equal' => 'A data de pagamento deve ser igual ou posterior à data de operação.',
            
            'items.required' => 'É necessário adicionar pelo menos um item à fatura.',
            'items.array' => 'Os itens devem ser uma lista.',
            'items.min' => 'É necessário pelo menos um item.',
            
            'items.*.name.required' => 'O nome do item é obrigatório.',
            'items.*.name.string' => 'O nome do item deve ser uma string.',
            'items.*.name.max' => 'O nome do item não pode exceder 255 caracteres.',
            
            'items.*.price.required' => 'O preço do item é obrigatório.',
            'items.*.price.numeric' => 'O preço deve ser um valor numérico.',
            'items.*.price.min' => 'O preço deve ser maior ou igual a zero.',
            
            'items.*.quantity.required' => 'A quantidade do item é obrigatória.',
            'items.*.quantity.integer' => 'A quantidade deve ser um número inteiro.',
            'items.*.quantity.min' => 'A quantidade deve ser pelo menos 1.',
            
            'items.*.discount.integer' => 'O desconto deve ser um número inteiro.',
            'items.*.discount.min' => 'O desconto deve ser maior ou igual a 0.',
            'items.*.discount.max' => 'O desconto não pode ser maior que 100%.',
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
            'client_name' => 'nome do cliente',
            'client_address' => 'endereço do cliente',
            'client_phone_number' => 'telefone do cliente',
            'client_nuit' => 'NUIT do cliente',
            'invoice_number' => 'número da fatura',
            'invoice_type' => 'tipo da fatura',
            'invoice_operation_date' => 'data de operação',
            'invoice_payment_date' => 'data de pagamento',
            'invoice_notes' => 'notas da fatura',
            'system_user' => 'usuário do sistema',
            'system_attendant' => 'atendente do sistema',
            'items' => 'itens',
            'items.*.name' => 'nome do item',
            'items.*.description' => 'descrição do item',
            'items.*.price' => 'preço do item',
            'items.*.quantity' => 'quantidade do item',
            'items.*.discount' => 'desconto do item',
        ];
    }
}