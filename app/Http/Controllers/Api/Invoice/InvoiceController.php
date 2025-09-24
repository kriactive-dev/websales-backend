<?php

namespace App\Http\Controllers\Api\Invoice;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $search = $request->get('search');
            $status = $request->get('status');

            $query = Invoice::with('items');

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('invoice_number', 'like', "%{$search}%")
                      ->orWhere('client_name', 'like', "%{$search}%")
                      ->orWhere('client_nuit', 'like', "%{$search}%");
                });
            }

            if ($status) {
                $query->where('invoice_status', $status);
            }

            $invoices = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $invoices,
                'message' => 'Faturas recuperadas com sucesso'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao recuperar faturas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'client_name' => 'required|string|max:255',
                'client_address' => 'nullable|string',
                'client_phone_number' => 'nullable|string|max:20',
                'client_nuit' => 'nullable|string|max:20',
                'invoice_number' => 'required|string|unique:invoices,invoice_number|max:255',
                'invoice_type' => 'nullable|string|max:255',
                'invoice_operation_date' => 'required|date',
                'invoice_payment_date' => 'nullable|date',
                'invoice_notes' => 'nullable|string',
                'system_user' => 'nullable|string|max:255',
                'system_attendant' => 'nullable|string|max:255',
                'items' => 'required|array|min:1',
                'items.*.name' => 'required|string|max:255',
                'items.*.description' => 'nullable|string',
                'items.*.price' => 'required|numeric|min:0',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.discount' => 'nullable|integer|min:0|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Calcular totais
            $subtotal = 0;
            $totalDiscount = 0;

            foreach ($request->items as $item) {
                $itemTotal = $item['price'] * $item['quantity'];
                $itemDiscount = $itemTotal * (($item['discount'] ?? 0) / 100);
                $subtotal += $itemTotal;
                $totalDiscount += $itemDiscount;
            }

            $discountAmount = $request->get('invoice_discount_amount', 0);
            $transshipmentAmount = $request->get('invoice_transshipment_amount', 0);
            $taxesAmount = $request->get('invoice_taxes_amount', 0);
            
            $totalAmount = $subtotal - $totalDiscount - $discountAmount + $transshipmentAmount + $taxesAmount;

            // Criar fatura
            $invoice = Invoice::create([
                'client_name' => $request->client_name,
                'client_address' => $request->client_address,
                'client_phone_number' => $request->client_phone_number,
                'client_nuit' => $request->client_nuit,
                'invoice_number' => $request->invoice_number,
                'invoice_type' => $request->get('invoice_type', 'Factura'),
                'invoice_status' => 'Pendente',
                'invoice_paid_amount' => 0,
                'invoice_pending_amount' => $totalAmount,
                'invoice_discount_amount' => $discountAmount,
                'invoice_transshipment_amount' => $transshipmentAmount,
                'invoice_taxes_amount' => $taxesAmount,
                'invoice_subtotal_amount' => $subtotal,
                'invoice_total_amount' => $totalAmount,
                'invoice_operation_date' => $request->invoice_operation_date,
                'invoice_payment_date' => $request->invoice_payment_date,
                'invoice_notes' => $request->invoice_notes,
                'system_user' => $request->system_user,
                'system_attendant' => $request->system_attendant,
            ]);

            // Criar itens da fatura
            foreach ($request->items as $item) {
                $itemTotal = $item['price'] * $item['quantity'];
                $itemDiscount = $itemTotal * (($item['discount'] ?? 0) / 100);
                $finalTotal = $itemTotal - $itemDiscount;

                InvoiceItem::create([
                    'invoice' => $invoice->invoice_number,
                    'name' => $item['name'],
                    'description' => $item['description'] ?? null,
                    'price' => $item['price'],
                    'quantity' => $item['quantity'],
                    'discount' => $item['discount'] ?? 0,
                    'total' => $finalTotal,
                ]);
            }

            DB::commit();

            $invoice->load('items');

            return response()->json([
                'success' => true,
                'data' => $invoice,
                'message' => 'Fatura criada com sucesso'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar fatura: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $invoice = Invoice::with('items')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $invoice,
                'message' => 'Fatura recuperada com sucesso'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fatura não encontrada'
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $invoice = Invoice::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'client_name' => 'sometimes|required|string|max:255',
                'client_address' => 'nullable|string',
                'client_phone_number' => 'nullable|string|max:20',
                'client_nuit' => 'nullable|string|max:20',
                'invoice_number' => 'sometimes|required|string|max:255|unique:invoices,invoice_number,' . $invoice->id,
                'invoice_type' => 'nullable|string|max:255',
                'invoice_status' => 'nullable|string|max:255',
                'invoice_paid_amount' => 'nullable|numeric|min:0',
                'invoice_operation_date' => 'sometimes|required|date',
                'invoice_payment_date' => 'nullable|date',
                'invoice_notes' => 'nullable|string',
                'system_user' => 'nullable|string|max:255',
                'system_attendant' => 'nullable|string|max:255',
                'items' => 'sometimes|array|min:1',
                'items.*.name' => 'required_with:items|string|max:255',
                'items.*.description' => 'nullable|string',
                'items.*.price' => 'required_with:items|numeric|min:0',
                'items.*.quantity' => 'required_with:items|integer|min:1',
                'items.*.discount' => 'nullable|integer|min:0|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Atualizar dados da fatura
            $updateData = $request->only([
                'client_name', 'client_address', 'client_phone_number', 'client_nuit',
                'invoice_number', 'invoice_type', 'invoice_status', 'invoice_paid_amount',
                'invoice_operation_date', 'invoice_payment_date', 'invoice_notes',
                'system_user', 'system_attendant'
            ]);

            // Se estiver atualizando o status de pagamento
            if ($request->has('invoice_paid_amount')) {
                $paidAmount = $request->invoice_paid_amount;
                $totalAmount = $invoice->invoice_total_amount;
                $updateData['invoice_pending_amount'] = $totalAmount - $paidAmount;
                
                if ($paidAmount >= $totalAmount) {
                    $updateData['invoice_status'] = 'Pago';
                } elseif ($paidAmount > 0) {
                    $updateData['invoice_status'] = 'Parcial';
                } else {
                    $updateData['invoice_status'] = 'Pendente';
                }
            }

            // Se estiver atualizando itens, recalcular totais
            if ($request->has('items')) {
                // Remover itens existentes
                InvoiceItem::where('invoice', $invoice->invoice_number)->delete();

                // Calcular novos totais
                $subtotal = 0;
                $totalDiscount = 0;

                foreach ($request->items as $item) {
                    $itemTotal = $item['price'] * $item['quantity'];
                    $itemDiscount = $itemTotal * (($item['discount'] ?? 0) / 100);
                    $subtotal += $itemTotal;
                    $totalDiscount += $itemDiscount;
                }

                $discountAmount = $request->get('invoice_discount_amount', $invoice->invoice_discount_amount);
                $transshipmentAmount = $request->get('invoice_transshipment_amount', $invoice->invoice_transshipment_amount);
                $taxesAmount = $request->get('invoice_taxes_amount', $invoice->invoice_taxes_amount);
                
                $totalAmount = $subtotal - $totalDiscount - $discountAmount + $transshipmentAmount + $taxesAmount;

                $updateData = array_merge($updateData, [
                    'invoice_discount_amount' => $discountAmount,
                    'invoice_transshipment_amount' => $transshipmentAmount,
                    'invoice_taxes_amount' => $taxesAmount,
                    'invoice_subtotal_amount' => $subtotal,
                    'invoice_total_amount' => $totalAmount,
                    'invoice_pending_amount' => $totalAmount - ($updateData['invoice_paid_amount'] ?? $invoice->invoice_paid_amount),
                ]);

                // Criar novos itens
                foreach ($request->items as $item) {
                    $itemTotal = $item['price'] * $item['quantity'];
                    $itemDiscount = $itemTotal * (($item['discount'] ?? 0) / 100);
                    $finalTotal = $itemTotal - $itemDiscount;

                    InvoiceItem::create([
                        'invoice' => $request->get('invoice_number', $invoice->invoice_number),
                        'name' => $item['name'],
                        'description' => $item['description'] ?? null,
                        'price' => $item['price'],
                        'quantity' => $item['quantity'],
                        'discount' => $item['discount'] ?? 0,
                        'total' => $finalTotal,
                    ]);
                }
            }

            $invoice->update($updateData);

            DB::commit();

            $invoice->load('items');

            return response()->json([
                'success' => true,
                'data' => $invoice,
                'message' => 'Fatura atualizada com sucesso'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar fatura: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $invoice = Invoice::findOrFail($id);
            
            // Remover itens da fatura
            InvoiceItem::where('invoice', $invoice->invoice_number)->delete();
            
            // Remover fatura
            $invoice->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Fatura removida com sucesso'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erro ao remover fatura: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update invoice payment status
     */
    public function updatePaymentStatus(Request $request, string $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'paid_amount' => 'required|numeric|min:0',
                'payment_date' => 'nullable|date',
                'notes' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $invoice = Invoice::findOrFail($id);
            
            $paidAmount = $request->paid_amount;
            $totalAmount = $invoice->invoice_total_amount;
            $pendingAmount = $totalAmount - $paidAmount;

            $status = 'Pendente';
            if ($paidAmount >= $totalAmount) {
                $status = 'Pago';
            } elseif ($paidAmount > 0) {
                $status = 'Parcial';
            }

            $invoice->update([
                'invoice_paid_amount' => $paidAmount,
                'invoice_pending_amount' => $pendingAmount,
                'invoice_status' => $status,
                'invoice_payment_date' => $request->payment_date,
                'invoice_notes' => $request->notes ?? $invoice->invoice_notes,
            ]);

            return response()->json([
                'success' => true,
                'data' => $invoice,
                'message' => 'Status de pagamento atualizado com sucesso'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar status de pagamento: ' . $e->getMessage()
            ], 500);
        }
    }
}
