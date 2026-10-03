<?php

namespace App\Services\Settings;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class SettingsService
{
    private const CACHE_PREFIX = 'settings.';

    private const CACHE_TTL_SECONDS = 3600;

    public function get(string $key, ?string $default = null): ?string
    {
        return Cache::remember(
            self::CACHE_PREFIX.$key,
            self::CACHE_TTL_SECONDS,
            function () use ($key, $default) {
                $setting = Setting::query()->where('key', $key)->first();

                if ($setting === null || $setting->value === null) {
                    return $default;
                }

                if ($setting->is_encrypted) {
                    try {
                        return Crypt::decryptString($setting->value);
                    } catch (\Throwable) {
                        return $default;
                    }
                }

                return $setting->value;
            }
        );
    }

    public function set(
        string $key,
        ?string $value,
        string $group = 'general',
        bool $isEncrypted = false,
        bool $isSensitive = false
    ): Setting {
        $storedValue = $value;

        if ($isEncrypted && $value !== null && $value !== '') {
            $storedValue = Crypt::encryptString($value);
        }

        $setting = Setting::query()->updateOrCreate(
            ['key' => $key],
            [
                'group' => $group,
                'value' => $storedValue,
                'is_encrypted' => $isEncrypted,
                'is_sensitive' => $isSensitive,
            ]
        );

        Cache::forget(self::CACHE_PREFIX.$key);

        return $setting;
    }

    public function forget(string $key): void
    {
        Setting::query()->where('key', $key)->delete();
        Cache::forget(self::CACHE_PREFIX.$key);
    }

    public function maskForDisplay(?string $value, int $visibleTail = 4): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $length = strlen($value);

        if ($length <= $visibleTail) {
            return str_repeat('•', $length);
        }

        return str_repeat('•', max(8, $length - $visibleTail)).substr($value, -$visibleTail);
    }

    public function getMasked(string $key): string
    {
        $setting = Setting::query()->where('key', $key)->first();

        if ($setting === null || ! $setting->is_sensitive) {
            return (string) $this->get($key, '');
        }

        return $this->maskForDisplay($this->get($key));
    }
}
