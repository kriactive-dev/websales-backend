<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $guarded = [];

    protected $casts = [
        'invoice_operation_date' => 'date',
        'invoice_payment_date' => 'date',
        'invoice_paid_amount' => 'float',
        'invoice_pending_amount' => 'float',
        'invoice_discount_amount' => 'float',
        'invoice_transshipment_amount' => 'float',
        'invoice_taxes_amount' => 'float',
        'invoice_subtotal_amount' => 'float',
        'invoice_total_amount' => 'float',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class, 'invoice', 'invoice_number');
    }
}
