<?php

namespace App\Models;

use App\Support\Media\PublicAsset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Post extends Model
{
    use SoftDeletes, LogsActivity;

    protected $guarded = [];

    protected $casts = [
        'publish_at' => 'datetime',
    ];

    public function getFeaturedImageAttribute(?string $value): ?string
    {
        return PublicAsset::normalizePath($value);
    }

    public function setFeaturedImageAttribute(?string $value): void
    {
        $this->attributes['featured_image'] = PublicAsset::normalizePath($value);
    }

    public function tag(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Tag::class);
    }

    public function author(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'author_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }
}
