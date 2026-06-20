<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\TransactionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'purchase_request_id',
    'buyer_id',
    'seller_id',
    'amount',
    'kpay_payment_id',
    'kpay_disburse_id',
    'status',
    'payment_method',
    'buyer_phone',
    'seller_phone',
    'pickup_code',
    'pickup_code_used_at',
    'auto_release_at'
])]
class Transaction extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => TransactionStatus::class,
            'payment_method' => PaymentMethod::class,
            'amount' => 'decimal:2',
            'pickup_code_used_at' => 'datetime',
            'auto_release_at' => 'datetime'
        ];
    }

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function dispute(): HasOne
    {
        return $this->hasOne(Dispute::class);
    }
}
