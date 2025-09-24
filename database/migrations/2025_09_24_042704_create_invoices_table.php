<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            //client
            $table->string('client_name')->nullable();
            $table->string('client_address')->nullable();
            $table->string('client_phone_number')->nullable();
            $table->string('client_nuit')->nullable();
            //invoice
            $table->string('invoice_number')->unique();
            $table->string('invoice_type')->default('Factura')->nullable();
            $table->string('invoice_status')->default('Pendente');
            $table->double('invoice_paid_amount', '10', '2')->nullable();
            $table->double('invoice_pending_amount', '10', '2')->nullable();
            $table->double('invoice_discount_amount', '10', '2')->nullable();
            $table->double('invoice_transshipment_amount', '10', '2')->nullable();
            $table->double('invoice_taxes_amount', '10', '2')->nullable();
            $table->double('invoice_subtotal_amount', '10', '2')->nullable();
            $table->double('invoice_total_amount', '10', '2')->nullable();
            //invoice dates
            $table->date('invoice_operation_date')->nullable();
            $table->date('invoice_payment_date')->nullable();
            $table->text('invoice_notes')->nullable();
            //system
            $table->string('system_user')->nullable();
            $table->string('system_attendant')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
