<?php

namespace App\Services;

use App\Enums\ListingStatus;
use App\Models\Listing;
use App\Models\User;
use Cache;
use Storage;

class ListingService
{
    /**
     * Create a new class instance.
     */
    public function __construct() {}

    public function store(User $user, array $data): Listing
    {
        $listing = $user->listings()->create([
            'category_id' => $data['category_id'],
            'title'       => $data['title'],
            'description' => $data['description'],
            'price'       => $data['price'],
            'quantity'    => $data['quantity'],
            'condition'   => $data['condition'],
        ]);

        $this->handleImages($listing, $data['images'], $data['primary_image'] ?? 0);

        Cache::tags(['listings'])->flush();

        return $listing->load(['images', 'category', 'seller']);
    }

    public function update(Listing $listing, array $data): Listing
    {
        $listing->update(array_filter([
            'category_id' => $data['category_id'] ?? null,
            'title'       => $data['title'] ?? null,
            'description' => $data['description'] ?? null,
            'price'       => $data['price'] ?? null,
            'quantity'    => $data['quantity'] ?? null,
            'condition'   => $data['condition'] ?? null,
        ], fn($v) => !is_null($v)));

        if (!empty($data['images'])) {
            $listing->images()->each(function ($image) {
                Storage::disk('public')->delete($image->path);
                $image->delete();
            });

            $this->handleImages($listing, $data['images'], $data['primary_image'] ?? 0);
        }

        Cache::tags(['listings'])->flush();
        Cache::forget("listing:{$listing->id}");
        Cache::forget("seller:{$listing->user_id}:listings");

        return $listing->load(['images', 'category', 'seller']);
    }

    public function destroy(Listing $listing): void
    {
        $listing->images()->each(function ($image) {
            Storage::disk('public')->delete($image->path);
            $image->delete();
        });

        $listing->delete();
        
        Cache::tags(['listings'])->flush();
        Cache::forget("listing:{$listing->id}");
        Cache::forget("seller:{$listing->user_id}:listings");
    }

    public function toggleStatus(Listing $listing): Listing
    {
        $newStatus = $listing->status === ListingStatus::ACTIVE
            ? ListingStatus::PAUSED
            : ListingStatus::ACTIVE;

        $listing->update(['status' => $newStatus]);

        Cache::tags(['listings'])->flush();
        Cache::forget("listing:{$listing->id}");
        Cache::forget("seller:{$listing->user_id}:listings");

        return $listing;
    }

    private function handleImages(Listing $listing, array $images, int $primaryIndex): void
    {
        foreach ($images as $index => $image) {
            $path = $image->store('listings', 'public');

            $listing->images()->create([
                'path'       => $path,
                'is_primary' => $index === $primaryIndex,
            ]);
        }
    }
}
