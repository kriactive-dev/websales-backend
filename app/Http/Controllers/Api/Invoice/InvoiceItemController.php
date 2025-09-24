<?php

namespace App\Http\Controllers\Api\Invoice;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class InvoiceItemController extends Controller
{
    /**
     * Display a listing of items for a specific invoice.
     */
    public function index(Request $request, string $invoiceNumber): JsonResponse
    {
        try {
            $invoice = Invoice::where('invoice_number', $invoiceNumber)->first();

            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fatura não encontrada'
                ], 404);
            }

            $items = InvoiceItem::where('invoice', $invoiceNumber)
                               ->orderBy('created_at', 'asc')
                               ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'invoice' => $invoice,
                    'items' => $items
                ],
                'message' => 'Itens da fatura recuperados com sucesso'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao recuperar itens da fatura: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created item in storage.
     */
    public function store(Request $request, string $invoiceNumber): JsonResponse
    {
        try {
            $invoice = Invoice::where('invoice_number', $invoiceNumber)->first();

            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fatura não encontrada'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'price' => 'required|numeric|min:0',
                'quantity' => 'required|integer|min:1',
                'discount' => 'nullable|integer|min:0|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Calcular total do item
            $itemTotal = $request->price * $request->quantity;
            $itemDiscount = $itemTotal * (($request->discount ?? 0) / 100);
            $finalTotal = $itemTotal - $itemDiscount;

            // Criar item
            $item = InvoiceItem::create([
                'invoice' => $invoiceNumber,
                'name' => $request->name,
                'description' => $request->description,
                'price' => $request->price,
                'quantity' => $request->quantity,
                'discount' => $request->discount ?? 0,
                'total' => $finalTotal,
            ]);

            // Recalcular totais da fatura
            $this->recalculateInvoiceTotals($invoice);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $item,
                'message' => 'Item adicionado com sucesso'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erro ao adicionar item: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified item.
     */
    public function show(string $invoiceNumber, string $itemId): JsonResponse
    {
        try {
            $item = InvoiceItem::where('invoice', $invoiceNumber)
                              ->where('id', $itemId)
                              ->first();

            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item não encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $item,
                'message' => 'Item recuperado com sucesso'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao recuperar item: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified item in storage.
     */
    public function update(Request $request, string $invoiceNumber, string $itemId): JsonResponse
    {
        try {
            $item = InvoiceItem::where('invoice', $invoiceNumber)
                              ->where('id', $itemId)
                              ->first();

            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item não encontrado'
                ], 404);
            }

            $invoice = Invoice::where('invoice_number', $invoiceNumber)->first();

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'price' => 'sometimes|required|numeric|min:0',
                'quantity' => 'sometimes|required|integer|min:1',
                'discount' => 'nullable|integer|min:0|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Atualizar dados do item
            $updateData = $request->only(['name', 'description', 'price', 'quantity', 'discount']);

            // Recalcular total se preço, quantidade ou desconto foram alterados
            if ($request->has(['price', 'quantity', 'discount'])) {
                $price = $request->get('price', $item->price);
                $quantity = $request->get('quantity', $item->quantity);
                $discount = $request->get('discount', $item->discount);

                $itemTotal = $price * $quantity;
                $itemDiscount = $itemTotal * ($discount / 100);
                $finalTotal = $itemTotal - $itemDiscount;

                $updateData['total'] = $finalTotal;
            }

            $item->update($updateData);

            // Recalcular totais da fatura
            $this->recalculateInvoiceTotals($invoice);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $item->fresh(),
                'message' => 'Item atualizado com sucesso'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar item: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified item from storage.
     */
    public function destroy(string $invoiceNumber, string $itemId): JsonResponse
    {
        try {
            $item = InvoiceItem::where('invoice', $invoiceNumber)
                              ->where('id', $itemId)
                              ->first();

            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item não encontrado'
                ], 404);
            }

            $invoice = Invoice::where('invoice_number', $invoiceNumber)->first();

            DB::beginTransaction();

            $item->delete();

            // Recalcular totais da fatura
            $this->recalculateInvoiceTotals($invoice);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Item removido com sucesso'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erro ao remover item: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Recalculate invoice totals based on items
     */
    private function recalculateInvoiceTotals(Invoice $invoice): void
    {
        $items = InvoiceItem::where('invoice', $invoice->invoice_number)->get();
        
        $subtotal = $items->sum('total');
        
        $discountAmount = $invoice->invoice_discount_amount ?? 0;
        $transshipmentAmount = $invoice->invoice_transshipment_amount ?? 0;
        $taxesAmount = $invoice->invoice_taxes_amount ?? 0;
        
        $totalAmount = $subtotal - $discountAmount + $transshipmentAmount + $taxesAmount;
        $pendingAmount = $totalAmount - ($invoice->invoice_paid_amount ?? 0);

        $invoice->update([
            'invoice_subtotal_amount' => $subtotal,
            'invoice_total_amount' => $totalAmount,
            'invoice_pending_amount' => $pendingAmount,
        ]);

        // Atualizar status baseado no valor pago
        if ($invoice->invoice_paid_amount >= $totalAmount) {
            $invoice->update(['invoice_status' => 'Pago']);
        } elseif ($invoice->invoice_paid_amount > 0) {
            $invoice->update(['invoice_status' => 'Parcial']);
        } else {
            $invoice->update(['invoice_status' => 'Pendente']);
        }
    }

    /**
     * Bulk update items for an invoice
     */
    public function bulkUpdate(Request $request, string $invoiceNumber): JsonResponse
    {
        try {
            $invoice = Invoice::where('invoice_number', $invoiceNumber)->first();

            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fatura não encontrada'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'items' => 'required|array|min:1',
                'items.*.id' => 'sometimes|exists:invoice_items,id',
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

            // Remover todos os itens existentes da fatura
            InvoiceItem::where('invoice', $invoiceNumber)->delete();

            // Criar/Atualizar itens
            $createdItems = [];
            foreach ($request->items as $itemData) {
                $itemTotal = $itemData['price'] * $itemData['quantity'];
                $itemDiscount = $itemTotal * (($itemData['discount'] ?? 0) / 100);
                $finalTotal = $itemTotal - $itemDiscount;

                $item = InvoiceItem::create([
                    'invoice' => $invoiceNumber,
                    'name' => $itemData['name'],
                    'description' => $itemData['description'] ?? null,
                    'price' => $itemData['price'],
                    'quantity' => $itemData['quantity'],
                    'discount' => $itemData['discount'] ?? 0,
                    'total' => $finalTotal,
                ]);

                $createdItems[] = $item;
            }

            // Recalcular totais da fatura
            $this->recalculateInvoiceTotals($invoice);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'invoice' => $invoice->fresh(),
                    'items' => $createdItems
                ],
                'message' => 'Itens atualizados com sucesso'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar itens: ' . $e->getMessage()
            ], 500);
        }
    }
}