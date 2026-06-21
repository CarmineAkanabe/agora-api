<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class CheckIfBanned
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $bearerToken = $request->bearerToken();

        if ($bearerToken) {
            $accessToken = PersonalAccessToken::findToken($bearerToken);

            if ($accessToken) {
                $user = $accessToken->tokenable;

                if ($user && $user->is_banned) {
                    return response()->json([
                        'message' => 'Your account has been banned.'
                    ], 403);
                }
            }
        }
        return $next($request);
    }
}
