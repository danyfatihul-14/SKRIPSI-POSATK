<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    use HasFactory;

    protected $primaryKey = 'transaction_id';

    protected $fillable = [
        'store_id',
        'user_id',
        'waiter_id',
        'cashier_id',
        'transaction_code',
        'customer_name',
        'subtotal',
        'tax',
        'discount',
        'total_amount',
        'status',
        'payment_method',
        'customer_type',
        'notes',
        'cash_paid',
        'cash_change',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'discount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'cash_paid' => 'decimal:2',
        'cash_change' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the store that owns the transaction.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id', 'store_id');
    }

    /**
     * Get the user that created the transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Get the waiter user.
     */
    public function waiter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'waiter_id', 'user_id');
    }

    /**
     * Get the cashier user.
     */
    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id', 'user_id');
    }

    /**
     * Helper accessor to get Waiter Name
     */
    public function getWaiterNameAttribute(): string
    {
        if ($this->waiter?->name) {
            return $this->waiter->name;
        }
        if ($this->user?->role === 'pelayan') {
            return $this->user->name;
        }
        if (!empty($this->notes) && preg_match('/Order dibuat oleh \[(.*?)\]/i', $this->notes, $m)) {
            return trim($m[1]);
        }
        return '-';
    }

    /**
     * Helper accessor to get Cashier Name
     */
    public function getCashierNameAttribute(): string
    {
        if ($this->cashier?->name) {
            return $this->cashier->name;
        }
        if (in_array($this->user?->role, ['cashier', 'owner', 'admin'], true)) {
            return $this->user->name;
        }
        if (!empty($this->notes) && preg_match('/Dibayar \[(.*?)\]/i', $this->notes, $m)) {
            return trim($m[1]);
        }
        return $this->user?->name ?? '-';
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate transaction code if not provided
        static::creating(function ($transaction) {
            if (empty($transaction->transaction_code)) {
                $transaction->transaction_code = 'TRX-' . date('Ymd') . '-' . strtoupper(uniqid());
            }
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class, 'transaction_id', 'transaction_id');
    }
}
