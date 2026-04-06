<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Province extends Model
{
    use LogsActivity;

    protected $fillable = ['code', 'name', 'type', 'is_active', 'parent_id'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }

    public function parent()
    {
        return $this->belongsTo(Province::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Province::class, 'parent_id');
    }
}
