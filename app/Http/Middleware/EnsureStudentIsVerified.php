<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Enums\VerificationStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudentIsVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        // Ensures banned students is blocked
        $user = $request->user();

        if ($user->is_banned) {
            return response()->json(['message' => 'Your account has been banned.'], 403);
        }

        if ($user->role === UserRole::ADMIN) {
            return $next($request);
        }

        if (
            !$user->studentProfile ||
            $user->studentProfile->verification_status !== VerificationStatus::APPROVED
        ) {
            return response()->json([
                'message' => 'Your account is pending verification.'
            ], 403);
        }

        return $next($request);

        // $user = $request->user();

        // if ($user->role === UserRole::ADMIN) {
        //     return $next($request);
        // }

        // if (
        //     !$user->studentProfile ||
        //     $user->studentProfile->verification_status !== VerificationStatus::APPROVED
        // ) {
        //     return response()->json([
        //         'message' => 'Your account is pending verification.'
        //     ], 403);
        // }

        // return $next($request);
    }
}
