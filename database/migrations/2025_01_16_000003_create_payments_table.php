<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('purchase_id')->constrained()->onDelete('cascade');
            
            // Información del pago
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('CLP');
            $table->enum('payment_method', ['transbank', 'transfer', 'cash'])->default('transbank');
            
            // Información de Transbank
            $table->string('transbank_token')->unique()->nullable();
            $table->string('transbank_order_id')->unique()->nullable();
            $table->string('transbank_session_id')->nullable();
            
            // Estado y respuesta
            $table->enum('status', ['pending', 'approved', 'rejected', 'failed', 'cancelled'])->default('pending');
            $table->string('authorization_code')->nullable();
            $table->string('response_code')->nullable();
            $table->json('transbank_response')->nullable();
            
            // Fechas y notas
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index(['user_id', 'status']);
            $table->index(['purchase_id', 'status']);
            $table->index('transbank_order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};