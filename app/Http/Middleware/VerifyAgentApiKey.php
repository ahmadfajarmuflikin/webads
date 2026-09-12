<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyAgentApiKey
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $expectedKey = config('meta.agent_api_key');
        
        $providedKey = $request->header('X-Agent-Key') 
            ?: $request->bearerToken() 
            ?: $request->header('Authorization');

        if (str_starts_with((string)$providedKey, 'Bearer ')) {
            $providedKey = substr($providedKey, 7);
        }

        if (empty($expectedKey) || $providedKey !== $expectedKey) {
            return response()->json([
                'error' => 'Unauthorized Agent Access',
                'message' => 'Invalid or missing X-Agent-Key or Bearer token.'
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
