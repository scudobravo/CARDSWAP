<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\LoggingService;
use Symfony\Component\HttpFoundation\Response;

class PerformanceLogging
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        // Esegui la richiesta
        $response = $next($request);

        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);

        $duration = $endTime - $startTime;
        $memoryUsed = $endMemory - $startMemory;

        // Log performance solo per richieste API
        if ($request->is('api/*')) {
            LoggingService::logApi(
                $request->path(),
                $request->method(),
                $response->getStatusCode(),
                $duration,
                [
                    'request_size' => strlen($request->getContent()),
                    'response_size' => strlen($response->getContent()),
                    'memory_used' => $memoryUsed,
                    'query_count' => $this->getQueryCount(),
                ]
            );
        }

        // Aggiungi header di performance per debug
        if (config('app.debug')) {
            $response->headers->set('X-Response-Time', round($duration * 1000, 2) . 'ms');
            $response->headers->set('X-Memory-Usage', $this->formatBytes($memoryUsed));
        }

        return $response;
    }

    /**
     * Ottiene il numero di query eseguite (se disponibile)
     */
    private function getQueryCount(): int
    {
        if (app()->bound('db')) {
            $queries = app('db')->getQueryLog();
            return count($queries);
        }

        return 0;
    }

    /**
     * Formatta i byte in formato leggibile
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
