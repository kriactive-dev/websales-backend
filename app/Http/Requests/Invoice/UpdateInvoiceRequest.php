<?php

namespace App\Http\Requests\Invoice;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInvoiceRequest extends FormRequest
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
        $invoiceId = $this->route('invoice'); // Assume que o parâmetro da rota é 'invoice'

        return [
            // Dados do cliente
            'client_name' => 'sometimes|required|string|max:255',
            'client_address' => 'nullable|string|max:500',
            'client_phone_number' => 'nullable|string|max:20',
            'client_nuit' => 'nullable|string|max:20',
            
            // Dados da fatura
            'invoice_number' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('invoices', 'invoice_number')->ignore($invoiceId)
            ],
            'invoice_type' => 'nullable|string|in:Factura,Proforma,Orçamento|max:255',
            'invoice_status' => 'nullable|string|in:Pendente,Parcial,Pago,Cancelado',
            'invoice_paid_amount' => 'nullable|numeric|min:0',
            'invoice_operation_date' => 'sometimes|required|date',
            'invoice_payment_date' => 'nullable|date',
            'invoice_notes' => 'nullable|string|max:1000',
            
            // Dados do sistema
            'system_user' => 'nullable|string|max:255',
            'system_attendant' => 'nullable|string|max:255',
            
            // Valores adicionais da fatura
            'invoice_discount_amount' => 'nullable|numeric|min:0',
            'invoice_transshipment_amount' => 'nullable|numeric|min:0',
            'invoice_taxes_amount' => 'nullable|numeric|min:0',
            
            // Itens da fatura (opcionais para update)
            'items' => 'sometimes|array|min:1',
            'items.*.id' => 'sometimes|exists:invoice_items,id',
            'items.*.name' => 'required_with:items|string|max:255',
            'items.*.description' => 'nullable|string|max:500',
            'items.*.price' => 'required_with:items|numeric|min:0',
            'items.*.quantity' => 'required_with:items|integer|min:1',
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
            'invoice_status.in' => 'O status deve ser: Pendente, Parcial, Pago ou Cancelado.',
            
            'invoice_paid_amount.numeric' => 'O valor pago deve ser um número.',
            'invoice_paid_amount.min' => 'O valor pago deve ser maior ou igual a zero.',
            
            'invoice_operation_date.required' => 'A data de operação é obrigatória.',
            'invoice_operation_date.date' => 'A data de operação deve ser uma data válida.',
            
            'invoice_payment_date.date' => 'A data de pagamento deve ser uma data válida.',
            
            'items.array' => 'Os itens devem ser uma lista.',
            'items.min' => 'É necessário pelo menos um item quando especificado.',
            
            'items.*.id.exists' => 'O item especificado não existe.',
            
            'items.*.name.required_with' => 'O nome do item é obrigatório.',
            'items.*.name.string' => 'O nome do item deve ser uma string.',
            'items.*.name.max' => 'O nome do item não pode exceder 255 caracteres.',
            
            'items.*.price.required_with' => 'O preço do item é obrigatório.',
            'items.*.price.numeric' => 'O preço deve ser um valor numérico.',
            'items.*.price.min' => 'O preço deve ser maior ou igual a zero.',
            
            'items.*.quantity.required_with' => 'A quantidade do item é obrigatória.',
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
            'invoice_status' => 'status da fatura',
            'invoice_paid_amount' => 'valor pago',
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