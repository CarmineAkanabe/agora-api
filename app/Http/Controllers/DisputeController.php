<?php

namespace App\Http\Controllers;

use App\Http\Requests\Disputes\StoreDisputeRequest;
use App\Http\Resources\DisputeResource;
use App\Models\Dispute;
use App\Services\DisputeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DisputeController extends Controller
{
    public function __construct(protected DisputeService $disputeService) {}

    public function store(StoreDisputeRequest $request): JsonResponse
    {
        $dispute = $this->disputeService->store(
            $request->user(),
            $request->validated()
        );

        return response()->json(new DisputeResource($dispute), 201);
    }

    public function index(Request $request): JsonResponse
    {
        $disputes = Dispute::where(function ($query) use ($request) {
            $query->whereHas('transaction', function ($q) use ($request) {
                $q->where('buyer_id', $request->user()->id)
                  ->orWhere('seller_id', $request->user()->id);
            });
        })
        ->with(['transaction', 'raisedBy'])
        ->latest()
        ->get();

        return response()->json(DisputeResource::collection($disputes));
    }

    public function show(Request $request, Dispute $dispute): JsonResponse
    {
        $transaction = $dispute->transaction;

        if (
            $request->user()->id !== $transaction->buyer_id &&
            $request->user()->id !== $transaction->seller_id
        ) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $dispute->load(['transaction', 'raisedBy', 'resolvedBy']);
        return response()->json(new DisputeResource($dispute));
    }
}
