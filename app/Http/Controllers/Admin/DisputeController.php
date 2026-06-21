<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResolveDisputeRequest;
use App\Http\Resources\DisputeResource;
use App\Models\Dispute;
use App\Services\NotificationService;
use App\Enums\DisputeStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DisputeController extends Controller
{
    public function __construct(protected NotificationService $notificationService) {}

    public function index(): JsonResponse
    {
        $disputes = Dispute::with(['transaction', 'raisedBy'])
            ->latest()
            ->get();

        return response()->json(DisputeResource::collection($disputes));
    }

    public function show(Dispute $dispute): JsonResponse
    {
        $dispute->load(['transaction.buyer', 'transaction.seller', 'raisedBy', 'resolvedBy']);
        return response()->json(new DisputeResource($dispute));
    }

    public function resolve(ResolveDisputeRequest $request, Dispute $dispute): JsonResponse
    {
        if ($dispute->status !== DisputeStatus::OPEN) {
            return response()->json(['message' => 'Dispute is not open.'], 422);
        }

        $dispute->update([
            'status'      => DisputeStatus::RESOLVED,
            'resolution'  => $request->validated()['resolution'],
            'resolved_by' => $request->user()->id,
            'resolved_at' => now(),
        ]);

        $transaction = $dispute->transaction;

        $this->notificationService->disputeResolved(
            $transaction->buyer,
            $transaction->seller,
            $request->validated()['resolution']
        );

        return response()->json(new DisputeResource($dispute->load(['raisedBy', 'resolvedBy'])));
    }

    public function close(Request $request, Dispute $dispute): JsonResponse
    {
        if ($dispute->status === DisputeStatus::CLOSED) {
            return response()->json(['message' => 'Dispute is already closed.'], 422);
        }

        $dispute->update([
            'status'      => DisputeStatus::CLOSED,
            'resolved_by' => $request->user()->id,
            'resolved_at' => now(),
        ]);

        return response()->json(['message' => 'Dispute closed.']);
    }
}
