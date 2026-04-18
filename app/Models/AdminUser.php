<?php

namespace App\Models;

use App\Support\Media\PublicAsset;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AdminUser extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, HasRoles, SoftDeletes, LogsActivity;

    protected $guarded = [];

    public function getAvatarUrlAttribute(?string $value): ?string
    {
        return PublicAsset::normalizePath($value);
    }

    public function setAvatarUrlAttribute(?string $value): void
    {
        $this->attributes['avatar_url'] = PublicAsset::normalizePath($value);
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return PublicAsset::url($this->avatar_url);
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'force_change_password' => 'boolean',
            'covered_areas' => 'array',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->status === 'active';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('admin_users')
            ->logAll()
            ->logExcept(['password', 'remember_token'])
            ->logOnlyDirty()
            ->dontLogIfAttributesChangedOnly(['updated_at']);
    }
}
