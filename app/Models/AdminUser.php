<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdminUser extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, HasRoles, SoftDeletes;

    protected $guarded = [];

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar_url ? \App\Models\SystemSetting::getUrl($this->avatar_url) : null;
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
}
