<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class RefreshTokenMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = JWTAuth::getToken();

        if ($token) {
            try {
                /**
                 * Get data and time created token
                 */
                $createdAt = Carbon::createFromTimestamp(
                    JWTAuth::getPayload($token)->get('iat')
                );

                /**
                 * Add 15 minute to token generation
                 */
                $expirationTime = $createdAt->addMinutes(15);

                if (Carbon::now()->gt($expirationTime)) {
                    $newToken = JWTAuth::refresh(JWTAuth::getToken());
                    $response = $next($request);
                    $response->headers->set('Authorization', 'Bearer ' . $newToken);

                    return $response;
                }

                return $next($request);
            } catch (\Exception $e) {
                return response()->json(['error' => 'token_expired'], 401);
            }
        } else {
            return response()->json(['error' => 'token_not_provided'], 401);
        }
    }
}
