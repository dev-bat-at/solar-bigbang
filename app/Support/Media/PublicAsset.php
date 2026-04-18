<?php

namespace App\Support\Media;

class PublicAsset
{
    public static function normalizePath(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        $path = ltrim($path, '/');

        if (str_starts_with($path, 'public/')) {
            $path = substr($path, strlen('public/'));
        }

        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        return $path;
    }

    public static function url(?string $path): ?string
    {
        $normalized = static::normalizePath($path);

        if (blank($normalized)) {
            return null;
        }

        if (filter_var($normalized, FILTER_VALIDATE_URL)) {
            return $normalized;
        }

        return asset($normalized);
    }
}
