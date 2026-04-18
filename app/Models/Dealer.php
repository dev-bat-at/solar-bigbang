<?php

namespace App\Models;

use App\Support\Media\PublicAsset;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Dealer extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, LogsActivity;

    protected $guarded = [];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'coverage_area' => 'array',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getAvatarAttribute(?string $value): ?string
    {
        return PublicAsset::normalizePath($value);
    }

    public function setAvatarAttribute(?string $value): void
    {
        $this->attributes['avatar'] = PublicAsset::normalizePath($value);
    }

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

    public function notifications()
    {
        return $this->hasMany(DealerNotification::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }
}
