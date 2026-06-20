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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained()->restrictOnDelete();
            $table->foreignId('buyer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('seller_id')->constrained('users')->restrictOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('kpay_payment_id')->nullable();
            $table->string('kpay_disburse_id')->nullable();
            // Enum field
            $table->string('status')->default('initiated');
            $table->string('payment_method')->nullable();

            $table->string('buyer_phone');
            $table->string('seller_phone');
            $table->string('pickup_code', 6)->nullable();
            $table->timestamp('pickup_code_used_at')->nullable();
            $table->timestamp('auto_release_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
