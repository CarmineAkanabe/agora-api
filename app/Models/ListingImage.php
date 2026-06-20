<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'listing_id',
    'path',
    'is_primary'
])]
class ListingImage extends Model
{
    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    public function listing()
    {
        return $this->belongsTo(Listing::class);
    }
}
