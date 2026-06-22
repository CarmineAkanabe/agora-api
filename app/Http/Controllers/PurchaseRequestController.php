<?php

namespace App\Http\Controllers;

use App\Http\Requests\PurchaseRequests\StorePurchaseRequestRequest;
use App\Http\Requests\PurchaseRequests\UpdateRequestStatusRequest;
use App\Http\Resources\PurchaseRequestResource;
use App\Models\PurchaseRequest;
use App\Services\PurchaseRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PurchaseRequestController extends Controller
{
    public function __construct(protected PurchaseRequestService $service) {}

    public function store(StorePurchaseRequestRequest $request): JsonResponse
    {
        $purchaseRequest = $this->service->store(
            $request->user(),
            $request->validated()
        );

        return response()->json(new PurchaseRequestResource($purchaseRequest), 201);
    }

    public function sent(Request $request): JsonResponse
    {
        $requests = $request->user()
            ->sentRequests()
            ->with(['listing.primaryImage', 'seller'])
            ->latest()
            ->get();

        return response()->json(PurchaseRequestResource::collection($requests));
    }

    public function received(Request $request): JsonResponse
    {
        $requests = $request->user()
            ->receivedRequests()
            ->with(['listing.primaryImage', 'buyer'])
            ->latest()
            ->get();

        return response()->json(PurchaseRequestResource::collection($requests));
    }

    public function show(Request $request, PurchaseRequest $purchaseRequest): JsonResponse
    {
        $this->authorize('view', $purchaseRequest);
        // if (
        //     $request->user()->id !== $purchaseRequest->buyer_id &&
        //     $request->user()->id !== $purchaseRequest->seller_id
        // ) {
        //     return response()->json(['message' => 'Unauthorized.'], 403);
        // }

        $purchaseRequest->load(['listing.images', 'buyer', 'seller']);
        return response()->json(new PurchaseRequestResource($purchaseRequest));
    }

    public function approve(UpdateRequestStatusRequest $request, PurchaseRequest $purchaseRequest): JsonResponse
    {
        $this->authorize('approve', $purchaseRequest);

        $purchaseRequest = $this->service->approve($purchaseRequest, $request->user());
        return response()->json(new PurchaseRequestResource($purchaseRequest));
    }

    public function reject(UpdateRequestStatusRequest $request, PurchaseRequest $purchaseRequest): JsonResponse
    {
        $this->authorize('reject', $purchaseRequest);

        $purchaseRequest = $this->service->reject(
            $purchaseRequest,
            $request->user(),
            $request->validated()['reason'] ?? null
        );

        return response()->json(new PurchaseRequestResource($purchaseRequest));
    }

    public function cancel(Request $request, PurchaseRequest $purchaseRequest): JsonResponse
    {
        $this->authorize('cancel', $purchaseRequest);
        
        $purchaseRequest = $this->service->cancel($purchaseRequest, $request->user());
        return response()->json(new PurchaseRequestResource($purchaseRequest));
    }
}
