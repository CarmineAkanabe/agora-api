<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ModerateListingRequest;
use App\Http\Resources\ListingCollection;
use App\Models\Listing;
use App\Services\ListingService;
use Cache;
use Illuminate\Http\JsonResponse;

class ListingController extends Controller
{
    public function __construct(protected ListingService $listingService) {}

    public function index(): JsonResponse
    {
        $listings = Listing::with(['seller', 'category', 'primaryImage'])
            ->latest()
            ->paginate(20);

        return response()->json(new ListingCollection($listings));
    }

    public function remove(ModerateListingRequest $request, Listing $listing): JsonResponse
    {
        $this->listingService->destroy($listing);

        Cache::forget('reports:overview');
        Cache::forget('reports:listings');

        return response()->json(['message' => 'Listing removed successfully.']);
    }
}
