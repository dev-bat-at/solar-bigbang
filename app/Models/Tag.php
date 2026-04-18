<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tag extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::saving(function (self $tag): void {
            $tag->name = $tag->name_vi ?: $tag->name;
        });
    }

    public function setColorAttribute(?string $value): void
    {
        $this->attributes['color'] = static::normalizeColor($value);
    }

    public function getApiColorAttribute(): ?string
    {
        $color = static::normalizeColor($this->color);

        if (! $color) {
            return null;
        }

        return '0xFF'.str_replace('#', '', $color);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    protected static function normalizeColor(?string $color): ?string
    {
        if (! is_string($color)) {
            return null;
        }

        $hex = strtoupper(ltrim(trim($color), '#'));

        if (! preg_match('/^[0-9A-F]{6}$/', $hex)) {
            return null;
        }

        return "#{$hex}";
    }
}
