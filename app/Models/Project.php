<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Project extends Model
{
    use SoftDeletes, LogsActivity;

    protected $guarded = [];

    protected $casts = [
        'images' => 'array',
        'approved_at' => 'datetime',
        'completion_date' => 'date',
    ];

    public function dealer()
    {
        return $this->belongsTo(Dealer::class);
    }

    public function systemType()
    {
        return $this->belongsTo(SystemType::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }
}
