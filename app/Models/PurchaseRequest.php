<?php

namespace App\Models;

use App\Enums\RequestStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'listing_id',
    'buyer_id',
    'seller_id',
    'quantity',
    'total_price',
    'meeting_location',
    'whatsapp_number',
    'message',
    'status',
    'expires_at',
])]
class PurchaseRequest extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => RequestStatus::class,
            'total_price' => 'decimal:2',
            'expires_at' => 'datetime'
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function transaction(): HasOne
    {
        return $this->hasOne(Transaction::class);
    }
}
