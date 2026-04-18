<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DealerNotification extends Model
{
    public const STATUS_UNREAD = 'unread';
    public const STATUS_READ = 'read';

    public const TYPE_CUSTOMER_CONTACT = 'customer_contact';
    public const TYPE_PROJECT_APPROVED = 'project_approved';
    public const TYPE_PROJECT_REJECTED = 'project_rejected';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'read_at' => 'datetime',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            static::STATUS_UNREAD => 'Chưa đọc',
            static::STATUS_READ => 'Đã đọc',
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return static::statusOptions()[$this->status] ?? $this->status;
    }

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }
}
