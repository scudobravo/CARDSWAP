<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Logga l'uso di endpoint Shippo deprecati (CardSwap V1 usa AfterShip / Shipping V1).
 * Usato come middleware sulle route Shippo per compatibilità con route:cache.
 */
class LogShippoDeprecated
{
    public function handle(Request $request, Closure $next, string $level = 'warning'): Response
    {
        $path = $request->path();
        $useError = ($level === 'error' || str_contains($path, 'webhook'));
        if ($useError) {
            Log::error('Shippo webhook called - Shippo is deprecated and not used by CardSwap Shipping V1', [
                'endpoint' => '/' . $path,
                'note' => 'CardSwap V1 uses AfterShip webhook exclusively for tracking',
            ]);
        } else {
            Log::warning('Shippo endpoint called - Shippo is deprecated and not used by CardSwap Shipping V1', [
                'endpoint' => '/' . $path,
                'note' => 'CardSwap V1 does not use Shippo for pricing/tracking/labels',
            ]);
        }

        return $next($request);
    }
}
