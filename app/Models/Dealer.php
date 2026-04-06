<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Dealer extends Model
{
    use SoftDeletes, LogsActivity;

    protected $guarded = [];

    protected $casts = [
        'coverage_area' => 'array',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Khách hàng thuộc đại lý này.
     */
    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * Các lead được gán cho đại lý.
     */
    public function leads()
    {
        return $this->hasMany(Lead::class);
    }

    /**
     * Các dự án/công trình của đại lý.
     */
    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }
}
