<?php

namespace App\Support;

class SensitiveDataMasker
{
    private const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'token',
        'access_token',
        'refresh_token',
        'api_token',
        'remember_token',
        'authorization',
        'secret',
        'secret_key',
        'private_key',
        'client_secret',
        'webhook_secret',
        'card_number',
        'cvv',
        'iban',
    ];

    public static function mask(mixed $value): mixed
    {
        if (is_array($value)) {
            return self::maskArray($value);
        }

        if (is_string($value)) {
            return self::maskStringIfSecret($value);
        }

        return $value;
    }

    private static function maskArray(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $normalizedKey = is_string($key) ? strtolower($key) : '';

            if (self::isSensitiveKey($normalizedKey)) {
                $result[$key] = self::maskedPlaceholder($value);
                continue;
            }

            if (is_array($value)) {
                $result[$key] = self::maskArray($value);
                continue;
            }

            $result[$key] = is_string($value) ? self::maskStringIfSecret($value) : $value;
        }

        return $result;
    }

    private static function isSensitiveKey(string $key): bool
    {
        foreach (self::SENSITIVE_KEYS as $sensitiveKey) {
            if ($key === $sensitiveKey || str_contains($key, $sensitiveKey)) {
                return true;
            }
        }

        return false;
    }

    private static function maskedPlaceholder(mixed $value): string
    {
        $len = is_string($value) ? strlen($value) : 0;
        return sprintf('[REDACTED:%d]', $len);
    }

    private static function maskStringIfSecret(string $value): string
    {
        if (strlen($value) > 24 && preg_match('/(sk_|pk_|rk_|Bearer\s+|token|secret)/i', $value) === 1) {
            return '[REDACTED:STRING]';
        }

        return $value;
    }
}
