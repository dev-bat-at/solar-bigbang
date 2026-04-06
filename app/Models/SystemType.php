<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Str;

class SystemType extends Model
{
    use SoftDeletes, LogsActivity;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'quote_is_active' => 'boolean',
            'quote_settings' => 'array',
            'quote_price_tiers' => 'array',
            'quote_recommendations' => 'array',
        ];
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });
        static::updating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
    }
}
