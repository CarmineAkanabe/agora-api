<?php

namespace App\Http\Controllers;

use App\Enums\ListingStatus;
use App\Http\Requests\Listings\StoreListingRequest;
use App\Http\Requests\Listings\UpdateListingRequest;
use App\Http\Resources\ListingCollection;
use App\Http\Resources\ListingResource;
use App\Models\Listing;
use App\Models\User;
use App\Services\ListingService;
use Cache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ListingController extends Controller
{
    public function __construct(protected ListingService $listingService) {}

    public function index(Request $request): JsonResponse
    {
        $cacheKey = 'listings:' . md5($request->getQueryString() ?? 'all');

        $listings = Cache::tags(['listings'])->remember($cacheKey, now()->addMinutes(10), function () {
            return QueryBuilder::for(Listing::class)
                ->allowedFilters(
                    AllowedFilter::exact('category_id'),
                    AllowedFilter::exact('condition'),
                    AllowedFilter::scope('price_between'),
                    AllowedFilter::partial('title'),
                )
                ->allowedSorts('price', 'created_at')
                ->where('status', ListingStatus::ACTIVE)
                ->with(['primaryImage', 'category', 'seller'])
                ->paginate(12);
        });

        return response()->json(new ListingCollection($listings));
    }

    public function show(Listing $listing): JsonResponse
    {
        $listing->load(['images', 'category', 'seller.studentProfile']);
        return response()->json(new ListingResource($listing));
    }

    public function sellerListings(User $user): JsonResponse
    {
        $listings = $user->listings()
            ->where('status', ListingStatus::ACTIVE)
            ->with(['primaryImage', 'category'])
            ->latest()
            ->get();

        return response()->json(ListingResource::collection($listings));
    }

    public function store(StoreListingRequest $request): JsonResponse
    {
        $listing = $this->listingService->store(
            $request->user(),
            $request->validated()
        );

        return response()->json(new ListingResource($listing), 201);
    }

    public function update(UpdateListingRequest $request, Listing $listing): JsonResponse
    {
        if ($request->user()->id !== $listing->user_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $listing = $this->listingService->update($listing, $request->validated());
        return response()->json(new ListingResource($listing));
    }

    public function destroy(Request $request, Listing $listing): JsonResponse
    {
        if ($request->user()->id !== $listing->user_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $this->listingService->destroy($listing);
        return response()->json(['message' => 'Listing deleted.']);
    }

    public function toggleStatus(Request $request, Listing $listing): JsonResponse
    {
        if ($request->user()->id !== $listing->user_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $listing = $this->listingService->toggleStatus($listing);
        return response()->json(new ListingResource($listing));
    }
}
