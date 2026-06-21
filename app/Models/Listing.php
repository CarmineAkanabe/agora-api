<?php

namespace App\Models;

use App\Enums\ListingCondition;
use App\Enums\ListingStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'user_id',
    'category_id',
    'title',
    'description',
    'price',
    'quantity',
    'condition',
    'status',
])]
class Listing extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'condition' => ListingCondition::class,
            'status' => ListingStatus::class,
            'price' => 'decimal:2'
        ];
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ListingImage::class);
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(ListingImage::class)->where('is_primary', true);
    }

    public function purchaseRequests(): HasMany
    {
        return $this->hasMany(PurchaseRequest::class);
    }

    public function scopePriceBetween($query, string $value)
    {
        $parts = explode(',', $value);

        if (count($parts) !== 2) {
            return $query;
        }

        [$min, $max] = $parts;
        return $query->whereBetween('price', [(float)$min, (float)$max]);
    }
}
