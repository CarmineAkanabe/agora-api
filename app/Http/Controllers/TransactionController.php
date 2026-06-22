<?php

namespace App\Http\Controllers;

use App\Http\Requests\Transactions\InitiatePaymentRequest;
use App\Http\Resources\TransactionResource;
use App\Jobs\PollPaymentStatusJob;
use App\Models\PurchaseRequest;
use App\Models\Transaction;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function __construct(protected PaymentService $paymentService)   {}

    public function store(InitiatePaymentRequest $request): JsonResponse
    {
        $purchaseRequest = PurchaseRequest::findOrFail(
            $request->validated()['purchase_request_id']
        );

        if ($purchaseRequest->buyer_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ($purchaseRequest->status->value !== 'approved') {
            return response()->json([
                'message' => 'This request is not approved for payment.'
            ], 422);
        }

        if ($purchaseRequest->expires_at->isPast()) {
            return response()->json([
                'message' => 'Payment window has expired.'
            ], 422);
        }

        if ($purchaseRequest->transaction()->exists()) {
            return response()->json([
                'message' => 'A transaction already exists for this request.'
            ], 409);
        }

        $transaction = $this->paymentService->initiate(
            $purchaseRequest,
            $request->validated()
        );

        PollPaymentStatusJob::dispatch($transaction);

        return response()->json(new TransactionResource($transaction), 201);
    }

    public function index(Request $request): JsonResponse
    {
        $transactions = Transaction::where(function ($query) use ($request) {
            $query->where('buyer_id', $request->user()->id)
                  ->orWhere('seller_id', $request->user()->id);
        })
        ->with(['purchaseRequest.listing.primaryImage', 'buyer', 'seller'])
        ->latest()
        ->get();

        return response()->json(TransactionResource::collection($transactions));
    }

    public function show(Request $request, Transaction $transaction): JsonResponse
    {
        $this->authorize('view', $transaction);

        $transaction->load(['purchaseRequest.listing', 'buyer', 'seller']);
        return response()->json(new TransactionResource($transaction));
    }
}
