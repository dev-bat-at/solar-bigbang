<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SupportRequest extends Model
{
    use SoftDeletes, LogsActivity;

    protected $guarded = [];

    protected $casts = [
        'request_payload' => 'array',
        'handled_at' => 'datetime',
    ];

    public static function requestTypeOptions(): array
    {
        return [
            'general_contact' => 'Liên hệ trực tiếp',
            'product_quote' => 'Báo giá sản phẩm',
            'system_quote' => 'Báo giá theo hệ',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            'new' => 'Mới nhận',
            'contacted' => 'Đã liên hệ',
            'quoted' => 'Đã gửi báo giá',
            'resolved' => 'Hoàn tất',
            'cancelled' => 'Đã hủy',
        ];
    }

    public static function sourceOptions(): array
    {
        return [
            'admin_manual' => 'Admin nhập tay',
            'website' => 'Website',
            'api' => 'API',
            'phone' => 'Điện thoại',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function systemType()
    {
        return $this->belongsTo(SystemType::class);
    }

    public function getTargetLabelAttribute(): ?string
    {
        return match ($this->request_type) {
            'product_quote' => $this->product?->name_vi,
            'system_quote' => $this->systemType?->name,
            default => null,
        };
    }

    public function getRequestTypeLabelAttribute(): string
    {
        return static::requestTypeOptions()[$this->request_type] ?? $this->request_type;
    }

    public function getStatusLabelAttribute(): string
    {
        return static::statusOptions()[$this->status] ?? $this->status;
    }

    public function getSourceLabelAttribute(): string
    {
        return static::sourceOptions()[$this->source] ?? $this->source;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }
}
