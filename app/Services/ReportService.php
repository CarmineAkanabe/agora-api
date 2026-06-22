<?php

namespace App\Services;

use App\Enums\ListingStatus;
use App\Enums\RequestStatus;
use App\Enums\TransactionStatus;
use App\Enums\VerificationStatus;
use App\Models\Listing;
use App\Models\PurchaseRequest;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Review;
use App\Models\Dispute;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function overview(): array
    {
        return Cache::remember('reports:overview', now()->addMinutes(30), function () {
            return [
                'total_users'        => User::where('role', 'student')->count(),
                'pending_verifications' => User::whereHas('studentProfile', fn($q) =>
                    $q->where('verification_status', VerificationStatus::PENDING)
                )->count(),
                'banned_users'       => User::where('is_banned', true)->count(),
                'total_listings'     => Listing::count(),
                'active_listings'    => Listing::where('status', ListingStatus::ACTIVE)->count(),
                'total_transactions' => Transaction::count(),
                'total_revenue'      => Transaction::where('status', TransactionStatus::RELEASED)
                                            ->sum('amount'),
                'held_escrow'        => Transaction::where('status', TransactionStatus::HELD)
                                            ->sum('amount'),
                'open_disputes'      => Dispute::where('status', 'open')->count(),
                'total_reviews'      => Review::count(),
                'average_rating'     => round(Review::avg('rating'), 1),
            ];
        });
    }

    public function transactions(): array
    {
        return Cache::remember('reports:transactions', now()->addMinutes(15), function () {
            $monthly = Transaction::select(
                DB::raw("TO_CHAR(created_at, 'YYYY-MM') as month"),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(amount) as revenue')
            )
            ->where('status', TransactionStatus::RELEASED)
            ->groupBy('month')
            ->orderBy('month')
            ->get();

            return [
                'monthly'           => $monthly,
                'total_released'    => Transaction::where('status', TransactionStatus::RELEASED)->count(),
                'total_held'        => Transaction::where('status', TransactionStatus::HELD)->count(),
                'total_failed'      => Transaction::where('status', TransactionStatus::FAILED)->count(),
                'total_refunded'    => Transaction::where('status', TransactionStatus::REFUNDED)->count(),
            ];
        });
    }

    public function listings(): array
    {
        return Cache::remember('reports:listings', now()->addMinutes(15), function () {
            $byCategory = Listing::select('category_id', DB::raw('COUNT(*) as total'))
                ->with('category:id,name')
                ->groupBy('category_id')
                ->get();

            $byCondition = Listing::select('condition', DB::raw('COUNT(*) as total'))
                ->groupBy('condition')
                ->get();

            return [
                'by_category'  => $byCategory,
                'by_condition' => $byCondition,
                'total_active' => Listing::where('status', ListingStatus::ACTIVE)->count(),
                'total_sold'   => Listing::where('status', ListingStatus::SOLD)->count(),
                'total_removed'=> Listing::where('status', ListingStatus::REMOVED)->count(),
            ];
        });
    }

    public function users(): array
    {
        return Cache::remember('reports:users', now()->addMinutes(30), function () {
            $monthly = User::select(
                DB::raw("TO_CHAR(created_at, 'YYYY-MM') as month"),
                DB::raw('COUNT(*) as total')
            )
            ->where('role', 'student')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

            return [
                'monthly'              => $monthly,
                'total_verified'       => User::whereHas('studentProfile', fn($q) =>
                    $q->where('verification_status', VerificationStatus::APPROVED)
                )->count(),
                'total_pending'        => User::whereHas('studentProfile', fn($q) =>
                    $q->where('verification_status', VerificationStatus::PENDING)
                )->count(),
                'total_rejected'       => User::whereHas('studentProfile', fn($q) =>
                    $q->where('verification_status', VerificationStatus::REJECTED)
                )->count(),
            ];
        });
    }

    public function studentStats(int $userId): array
    {
        return Cache::remember("reports:student:{$userId}", now()->addMinutes(10), function () use ($userId) {
            return [
                'total_listings'      => Listing::where('user_id', $userId)->count(),
                'active_listings'     => Listing::where('user_id', $userId)
                                            ->where('status', ListingStatus::ACTIVE)->count(),
                'total_sales'         => Transaction::where('seller_id', $userId)
                                            ->where('status', TransactionStatus::RELEASED)->count(),
                'total_earned'        => Transaction::where('seller_id', $userId)
                                            ->where('status', TransactionStatus::RELEASED)
                                            ->sum('amount'),
                'total_purchases'     => Transaction::where('buyer_id', $userId)
                                            ->where('status', TransactionStatus::RELEASED)->count(),
                'total_spent'         => Transaction::where('buyer_id', $userId)
                                            ->where('status', TransactionStatus::RELEASED)
                                            ->sum('amount'),
                'pending_requests'    => PurchaseRequest::where('seller_id', $userId)
                                            ->where('status', RequestStatus::PENDING)->count(),
                'average_rating'      => round(Review::where('reviewee_id', $userId)->avg('rating'), 1),
                'total_reviews'       => Review::where('reviewee_id', $userId)->count(),
            ];
        });
    }
}
