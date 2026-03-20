<?php

namespace App\Services;

use App\Support\SensitiveDataMasker;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Auth;

class LoggingService
{
    private static function sanitizeContext(array $context): array
    {
        /** @var array $sanitized */
        $sanitized = SensitiveDataMasker::mask($context);
        return $sanitized;
    }

    /**
     * Livelli di log
     */
    const LEVELS = [
        'emergency' => 'emergency',
        'alert' => 'alert',
        'critical' => 'critical',
        'error' => 'error',
        'warning' => 'warning',
        'notice' => 'notice',
        'info' => 'info',
        'debug' => 'debug',
    ];

    /**
     * Log di errore con contesto
     */
    public static function logError(\Exception $exception, array $context = [])
    {
        $context = array_merge([
            'user_id' => Auth::id(),
            'ip' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'url' => Request::fullUrl(),
            'method' => Request::method(),
            'timestamp' => now()->toISOString(),
            'exception_class' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ], $context);

        Log::error($exception->getMessage(), self::sanitizeContext($context));
    }

    /**
     * Log di performance
     */
    public static function logPerformance(string $operation, float $duration, array $context = [])
    {
        $context = array_merge([
            'user_id' => Auth::id(),
            'ip' => Request::ip(),
            'url' => Request::fullUrl(),
            'method' => Request::method(),
            'timestamp' => now()->toISOString(),
            'operation' => $operation,
            'duration_ms' => round($duration * 1000, 2),
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
        ], $context);

        // Log solo se la durata supera una soglia
        if ($duration > 1.0) { // > 1 secondo
            Log::warning("Slow operation: {$operation}", self::sanitizeContext($context));
        } else {
            Log::info("Performance: {$operation}", self::sanitizeContext($context));
        }
    }

    /**
     * Log di attività utente
     */
    public static function logUserActivity(string $action, array $context = [])
    {
        $context = array_merge([
            'user_id' => Auth::id(),
            'ip' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'url' => Request::fullUrl(),
            'method' => Request::method(),
            'timestamp' => now()->toISOString(),
            'action' => $action,
        ], $context);

        Log::info("User activity: {$action}", self::sanitizeContext($context));
    }

    /**
     * Log di sicurezza
     */
    public static function logSecurity(string $event, array $context = [])
    {
        $context = array_merge([
            'user_id' => Auth::id(),
            'ip' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'url' => Request::fullUrl(),
            'method' => Request::method(),
            'timestamp' => now()->toISOString(),
            'security_event' => $event,
        ], $context);

        Log::warning("Security event: {$event}", self::sanitizeContext($context));
    }

    /**
     * Log di business logic
     */
    public static function logBusiness(string $event, array $data = [])
    {
        $context = [
            'user_id' => Auth::id(),
            'timestamp' => now()->toISOString(),
            'business_event' => $event,
            'data' => $data,
        ];

        Log::info("Business event: {$event}", self::sanitizeContext($context));
    }

    /**
     * Log di API
     */
    public static function logApi(string $endpoint, string $method, int $statusCode, float $duration, array $context = [])
    {
        $context = array_merge([
            'user_id' => Auth::id(),
            'ip' => Request::ip(),
            'timestamp' => now()->toISOString(),
            'endpoint' => $endpoint,
            'method' => $method,
            'status_code' => $statusCode,
            'duration_ms' => round($duration * 1000, 2),
            'response_size' => strlen(json_encode($context['response'] ?? '')),
        ], $context);

        if ($statusCode >= 400) {
            Log::error("API error: {$method} {$endpoint}", self::sanitizeContext($context));
        } else {
            Log::info("API call: {$method} {$endpoint}", self::sanitizeContext($context));
        }
    }

    /**
     * Log di database
     */
    public static function logDatabase(string $query, float $duration, array $context = [])
    {
        $context = array_merge([
            'user_id' => Auth::id(),
            'timestamp' => now()->toISOString(),
            'query' => $query,
            'duration_ms' => round($duration * 1000, 2),
        ], $context);

        // Log solo query lente
        if ($duration > 0.5) { // > 500ms
            Log::warning("Slow query detected", self::sanitizeContext($context));
        }
    }

    /**
     * Log di cache
     */
    public static function logCache(string $operation, string $key, bool $hit, array $context = [])
    {
        $context = array_merge([
            'user_id' => Auth::id(),
            'timestamp' => now()->toISOString(),
            'cache_operation' => $operation,
            'cache_key' => $key,
            'cache_hit' => $hit,
        ], $context);

        Log::debug("Cache {$operation}: {$key}", self::sanitizeContext($context));
    }

    /**
     * Log di email
     */
    public static function logEmail(string $to, string $subject, string $status, array $context = [])
    {
        $context = array_merge([
            'user_id' => Auth::id(),
            'timestamp' => now()->toISOString(),
            'email_to' => $to,
            'email_subject' => $subject,
            'email_status' => $status,
        ], $context);

        Log::info("Email sent: {$subject}", self::sanitizeContext($context));
    }

    /**
     * Log di file upload
     */
    public static function logFileUpload(string $filename, int $size, string $type, string $status, array $context = [])
    {
        $context = array_merge([
            'user_id' => Auth::id(),
            'ip' => Request::ip(),
            'timestamp' => now()->toISOString(),
            'filename' => $filename,
            'file_size' => $size,
            'file_type' => $type,
            'upload_status' => $status,
        ], $context);

        Log::info("File upload: {$filename}", self::sanitizeContext($context));
    }

    /**
     * Log di pagamento
     */
    public static function logPayment(string $transactionId, string $status, float $amount, string $currency, array $context = [])
    {
        $context = array_merge([
            'user_id' => Auth::id(),
            'timestamp' => now()->toISOString(),
            'transaction_id' => $transactionId,
            'payment_status' => $status,
            'amount' => $amount,
            'currency' => $currency,
        ], $context);

        Log::info("Payment: {$status}", self::sanitizeContext($context));
    }

    /**
     * Log di sistema
     */
    public static function logSystem(string $component, string $event, array $context = [])
    {
        $context = array_merge([
            'timestamp' => now()->toISOString(),
            'system_component' => $component,
            'system_event' => $event,
            'server_load' => sys_getloadavg()[0] ?? null,
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
        ], $context);

        Log::info("System: {$component} - {$event}", self::sanitizeContext($context));
    }

    /**
     * Pulisce i log vecchi (da chiamare via cron)
     */
    public static function cleanOldLogs(int $days = 30)
    {
        $cutoffDate = now()->subDays($days);
        
        // Implementazione dipende dal driver di log utilizzato
        // Per file-based logging, potresti voler implementare una rotazione
        Log::info("Cleaning logs older than {$days} days", self::sanitizeContext([
            'cutoff_date' => $cutoffDate->toISOString(),
            'timestamp' => now()->toISOString(),
        ]));
    }

    /**
     * Ottiene statistiche dei log
     */
    public static function getLogStats(int $hours = 24)
    {
        $since = now()->subHours($hours);
        
        // Implementazione dipende dal driver di log utilizzato
        // Potresti voler implementare un sistema di analisi dei log
        return [
            'period_hours' => $hours,
            'since' => $since->toISOString(),
            'timestamp' => now()->toISOString(),
        ];
    }
}
