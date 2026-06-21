<?php

namespace App\Http\Controllers;

use App\Http\Resources\ListingImageResource;
use App\Models\Listing;
use App\Models\ListingImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ListingImageController extends Controller
{
    public function store(Request $request, Listing $listing): JsonResponse
    {
        if ($request->user()->id !== $listing->user_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'images'   => ['required', 'array', 'min:1'],
            'images.*' => ['image', 'max:2048'],
        ]);

        if ($listing->images()->count() + count($request->images) > 5) {
            return response()->json([
                'message' => 'A listing cannot have more than 5 images.'
            ], 422);
        }

        $uploaded = [];

        foreach ($request->file('images') as $image) {
            $path = $image->store('listings', 'public');

            $uploaded[] = $listing->images()->create([
                'path'       => $path,
                'is_primary' => false,
            ]);
        }

        return response()->json(ListingImageResource::collection(collect($uploaded)), 201);
    }

    public function destroy(Request $request, Listing $listing, ListingImage $image): JsonResponse
    {
        if ($request->user()->id !== $listing->user_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ($image->listing_id !== $listing->id) {
            return response()->json(['message' => 'Image does not belong to this listing.'], 422);
        }

        if ($listing->images()->count() === 1) {
            return response()->json([
                'message' => 'A listing must have at least one image.'
            ], 422);
        }

        Storage::disk('public')->delete($image->path);
        $image->delete();

        if ($image->is_primary) {
            $listing->images()->first()->update(['is_primary' => true]);
        }

        return response()->json(['message' => 'Image deleted.']);
    }

    public function setPrimary(Request $request, Listing $listing, ListingImage $image): JsonResponse
    {
        if ($request->user()->id !== $listing->user_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ($image->listing_id !== $listing->id) {
            return response()->json(['message' => 'Image does not belong to this listing.'], 422);
        }

        $listing->images()->update(['is_primary' => false]);
        $image->update(['is_primary' => true]);

        return response()->json(new ListingImageResource($image));
    }
}
