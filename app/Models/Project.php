<?php

namespace App\Models;

use App\Support\Media\PublicAsset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\DealerNotificationService;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Project extends Model
{
    use SoftDeletes, LogsActivity;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $guarded = [];

    protected $casts = [
        'images' => 'array',
        'price' => 'decimal:2',
        'approved_at' => 'datetime',
        'completion_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::updated(function (Project $project): void {
            if (! $project->wasChanged('status')) {
                return;
            }

            app(DealerNotificationService::class)->notifyProjectStatusChanged($project);
        });
    }

    public function dealer()
    {
        return $this->belongsTo(Dealer::class);
    }

    public function systemType()
    {
        return $this->belongsTo(SystemType::class);
    }

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function getImagesUrlsAttribute(): array
    {
        return collect($this->images ?? [])
            ->map(fn ($path) => PublicAsset::url($path))
            ->filter()
            ->values()
            ->all();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }
}
