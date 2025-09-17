<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'purchase_id',
        'amount',
        'currency',
        'payment_method',
        'transbank_token',
        'transbank_order_id',
        'transbank_session_id',
        'status',
        'authorization_code',
        'response_code',
        'transbank_response',
        'paid_at',
        'notes',
        'is_active'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transbank_response' => 'array',
        'paid_at' => 'datetime',
        'is_active' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    // Estados de pago
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    // Métodos de pago
    const METHOD_TRANSBANK = 'transbank';
    const METHOD_TRANSFER = 'transfer';
    const METHOD_CASH = 'cash';

    /**
     * Relación con el usuario
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con la compra
     */
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    /**
     * Scope para pagos aprobados
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope para pagos pendientes
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Verificar si el pago está aprobado
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Verificar si el pago está pendiente
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Obtener monto formateado
     */
    public function getFormattedAmountAttribute(): string
    {
        return '$' . number_format($this->amount, 0, ',', '.') . ' ' . $this->currency;
    }
}