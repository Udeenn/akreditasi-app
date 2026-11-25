<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-KEY')
            ?? $request->header('x-api-key')
            ?? $request->header('X_API_KEY')
            ?? $request->header('HTTP_X_API_KEY')
            ?? $request->bearerToken();

        $validKey = config('app.api_key');

        if (!$validKey) {
            return response()->json([
                'status' => 'error',
                'message' => 'Server API Key is not configured.'
            ], 500);
        }

        if (!is_string($apiKey) || !hash_equals((string) $validKey, $apiKey)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized. Invalid API Key.'
            ], 401);
        }

        return $next($request);
    }
}
