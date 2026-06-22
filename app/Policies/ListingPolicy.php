<?php

namespace App\Policies;

use App\Models\Listing;
use App\Models\User;

class ListingPolicy
{
    public function update(User $user, Listing $listing): bool
    {
        return $user->id === $listing->user_id;
    }

    public function delete(User $user, Listing $listing): bool
    {
        return $user->id === $listing->user_id;
    }

    public function toggleStatus(User $user, Listing $listing): bool
    {
        return $user->id === $listing->user_id;
    }

    public function addImages(User $user, Listing $listing): bool
    {
        return $user->id === $listing->user_id;
    }

    public function deleteImage(User $user, Listing $listing): bool
    {
        return $user->id === $listing->user_id;
    }
}
