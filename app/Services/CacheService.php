<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheService
{
    /**
     * Durata cache in minuti
     */
    const CACHE_DURATION = [
        'short' => 5,      // 5 minuti
        'medium' => 30,    // 30 minuti
        'long' => 120,     // 2 ore
        'very_long' => 1440, // 24 ore
    ];

    /**
     * Chiavi cache comuni
     */
    const CACHE_KEYS = [
        'categories' => 'categories:active',
        'card_sets' => 'card_sets:active',
        'players' => 'players:active',
        'teams' => 'teams:active',
        'leagues' => 'leagues:active',
        'filter_options' => 'filter_options',
        'homepage_stats' => 'homepage:stats',
        'popular_cards' => 'cards:popular',
        'recent_cards' => 'cards:recent',
    ];

    /**
     * Ottiene dati dalla cache o esegue callback
     */
    public static function remember(string $key, int $duration, callable $callback, array $tags = [])
    {
        try {
            if (!empty($tags)) {
                return Cache::tags($tags)->remember($key, $duration, $callback);
            }
            
            return Cache::remember($key, $duration, $callback);
        } catch (\Exception $e) {
            Log::error('Cache error: ' . $e->getMessage(), [
                'key' => $key,
                'duration' => $duration,
                'tags' => $tags
            ]);
            
            // Fallback: esegui callback senza cache
            return $callback();
        }
    }

    /**
     * Ottiene dati dalla cache con durata predefinita
     */
    public static function rememberShort(string $key, callable $callback, array $tags = [])
    {
        return self::remember($key, self::CACHE_DURATION['short'] * 60, $callback, $tags);
    }

    public static function rememberMedium(string $key, callable $callback, array $tags = [])
    {
        return self::remember($key, self::CACHE_DURATION['medium'] * 60, $callback, $tags);
    }

    public static function rememberLong(string $key, callable $callback, array $tags = [])
    {
        return self::remember($key, self::CACHE_DURATION['long'] * 60, $callback, $tags);
    }

    public static function rememberVeryLong(string $key, callable $callback, array $tags = [])
    {
        return self::remember($key, self::CACHE_DURATION['very_long'] * 60, $callback, $tags);
    }

    /**
     * Invalida cache per tag
     */
    public static function forgetByTags(array $tags)
    {
        try {
            Cache::tags($tags)->flush();
            Log::info('Cache invalidated for tags: ' . implode(', ', $tags));
        } catch (\Exception $e) {
            Log::error('Cache invalidation error: ' . $e->getMessage(), [
                'tags' => $tags
            ]);
        }
    }

    /**
     * Invalida cache per chiave
     */
    public static function forget(string $key)
    {
        try {
            Cache::forget($key);
            Log::info('Cache invalidated for key: ' . $key);
        } catch (\Exception $e) {
            Log::error('Cache invalidation error: ' . $e->getMessage(), [
                'key' => $key
            ]);
        }
    }

    /**
     * Pulisce tutta la cache
     */
    public static function flush()
    {
        try {
            Cache::flush();
            Log::info('All cache flushed');
        } catch (\Exception $e) {
            Log::error('Cache flush error: ' . $e->getMessage());
        }
    }

    /**
     * Ottiene statistiche cache
     */
    public static function getStats()
    {
        try {
            $driver = Cache::getStore();
            
            if (method_exists($driver, 'getStats')) {
                return $driver->getStats();
            }
            
            return [
                'driver' => config('cache.default'),
                'status' => 'active'
            ];
        } catch (\Exception $e) {
            Log::error('Cache stats error: ' . $e->getMessage());
            return [
                'driver' => config('cache.default'),
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Genera chiave cache con prefisso
     */
    public static function generateKey(string $prefix, array $params = []): string
    {
        $key = $prefix;
        
        if (!empty($params)) {
            $key .= ':' . md5(serialize($params));
        }
        
        return $key;
    }

    /**
     * Verifica se la cache è disponibile
     */
    public static function isAvailable(): bool
    {
        try {
            Cache::put('test_key', 'test_value', 1);
            $value = Cache::get('test_key');
            Cache::forget('test_key');
            
            return $value === 'test_value';
        } catch (\Exception $e) {
            Log::error('Cache availability check failed: ' . $e->getMessage());
            return false;
        }
    }
}
