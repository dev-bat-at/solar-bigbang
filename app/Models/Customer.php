<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Customer extends Model
{
    use SoftDeletes, LogsActivity;

    protected $guarded = [];

    public static function statusOptions(): array
    {
        return [
            'new' => 'Mới',
            'processing' => 'Đang xử lý',
            'completed' => 'Hoàn thành',
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return static::statusOptions()[$this->status] ?? $this->status;
    }

    /**
     * Đại lý sở hữu khách hàng này.
     */
    public function dealer()
    {
        return $this->belongsTo(Dealer::class);
    }

    public function systemType()
    {
        return $this->belongsTo(SystemType::class);
    }

    /**
     * Các lead của khách hàng.
     */
    public function leads()
    {
        return $this->hasMany(Lead::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }
}
