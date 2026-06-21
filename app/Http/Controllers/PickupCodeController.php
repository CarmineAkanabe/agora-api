<?php

namespace App\Http\Controllers;

use App\Http\Requests\Transactions\EnterPickupCodeRequest;
use App\Http\Resources\TransactionResource;
use App\Jobs\DisbursePaymentJob;
use App\Models\Transaction;
use App\Notifications\PickupCodeVerifiedNotification;
use App\Services\NotificationService;
use App\Services\PickupCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PickupCodeController extends Controller
{
    public function __construct(
        protected PickupCodeService $pickupCodeService,
        protected NotificationService $notificationService
        ) {}

    public function verify(EnterPickupCodeRequest $request, Transaction $transaction): JsonResponse
    {
        if ($request->user()->id !== $transaction->seller_id) {
            return response()->json(['message' => 'Only the seller can verify the pickup code.'], 403);
        }

        if ($transaction->status->value !== 'held') {
            return response()->json(['message' => 'Transaction is not in escrow.'], 422);
        }

        if (!$this->pickupCodeService->verify($transaction, $request->validated()['code'])) {
            return response()->json(['message' => 'Invalid or already used pickup code.'], 422);
        }

        $this->notificationService->pickupCodeVerified($transaction->buyer, $transaction->seller);

        // $transaction->buyer->notify(new PickupCodeVerifiedNotification());
        // $transaction->seller->notify(new PickupCodeVerifiedNotification());

        DisbursePaymentJob::dispatch($transaction);

        return response()->json([
            'message'     => 'Code verified. Disbursement in progress.',
            'transaction' => new TransactionResource($transaction->fresh()),
        ]);
    }
}
