<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Purchase extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'service_id',
        'quantity',
        'unit_price',
        'total_amount',
        'currency',
        'status',
        'expires_at',
        'activated_at',
        'notes',
        'is_active'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'expires_at' => 'datetime',
        'activated_at' => 'datetime',
        'is_active' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    // Estados de compra
    const STATUS_PENDING = 'pending';
    const STATUS_PAID = 'paid';
    const STATUS_ACTIVE = 'active';
    const STATUS_EXPIRED = 'expired';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Relación con el usuario
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con el servicio
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Relación con el pago (uno a uno)
     */
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    /**
     * Scope para compras activas
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope para compras pagadas
     */
    public function scopePaid($query)
    {
        return $query->whereIn('status', [self::STATUS_PAID, self::STATUS_ACTIVE]);
    }

    /**
     * Verificar si la compra está activa
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE && 
               ($this->expires_at === null || $this->expires_at->isFuture());
    }

    /**
     * Verificar si la compra está pagada
     */
    public function isPaid(): bool
    {
        return in_array($this->status, [self::STATUS_PAID, self::STATUS_ACTIVE]);
    }

    /**
     * Activar la compra después del pago
     */
    public function activate(): void
    {
        $this->update([
            'status' => self::STATUS_ACTIVE,
            'activated_at' => now(),
            'expires_at' => $this->service->duration_days 
                ? now()->addDays($this->service->duration_days) 
                : null
        ]);
    }

    /**
     * Obtener total formateado
     */
    public function getFormattedTotalAttribute(): string
    {
        return '$' . number_format($this->total_amount, 0, ',', '.') . ' ' . $this->currency;
    }
}