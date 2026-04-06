<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    protected $primaryKey = 'key';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['key', 'value', 'type', 'description'];

    public static function get($key, $default = null)
    {
        try {
            return Cache::rememberForever("system_setting_{$key}", function () use ($key, $default) {
                if (!\Illuminate\Support\Facades\Schema::hasTable('system_settings')) {
                    return $default;
                }
                $setting = self::where('key', $key)->first();
                return $setting ? $setting->value : $default;
            });
        } catch (\Throwable $e) {
            return $default;
        }
    }

    public static function getUrl($key, $default = null)
    {
        $value = self::get($key);
        if (!$value)
            return $default;

        if (str_starts_with($value, 'http'))
            return $value;

        return rtrim(env('APP_URL', 'http://localhost'), '/') . '/' . ltrim($value, '/');
    }

    public static function set($key, $value, $group = null)
    {
        $setting = self::firstOrNew(['key' => $key]);
        $setting->value = $value;
        if ($group) {
            $setting->description = $group; // Reuse description to store group
        }
        $setting->save();

        Cache::forget("system_setting_{$key}");
        return $setting;
    }

    public static function clearCache()
    {
        $settings = self::all();
        foreach ($settings as $setting) {
            Cache::forget("system_setting_{$setting->key}");
        }
    }
}
