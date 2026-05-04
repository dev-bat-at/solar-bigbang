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

    protected const REQUEST_PAYLOAD_MONEY_KEYS = [
        'monthly_bill',
        'amount',
        'price',
        'cost',
        'budget',
        'total',
        'estimated_value',
    ];

    protected const REQUEST_PAYLOAD_MODE_LABELS = [
        'system_quote_standard' => 'Biểu mẫu báo giá theo hệ',
        'system_quote_custom' => 'Biểu mẫu công thức tính',
        'day_night_ratio' => 'Biểu mẫu tỷ lệ ngày đêm',
        'custom_fields' => 'Biểu mẫu field tùy chỉnh',
    ];

    protected const REQUEST_PAYLOAD_KEY_LABELS = [
        'monthly_bill' => 'Tiền điện trung bình tháng',
        'phase_type' => 'Loại điện',
        'start_day' => 'Tỷ lệ điện ban ngày (%)',
        'end_night' => 'Tỷ lệ điện ban đêm (%)',
        'day_ratio' => 'Tỷ lệ điện ban ngày (%)',
        'night_ratio' => 'Tỷ lệ điện ban đêm (%)',
        'name' => 'Họ và tên',
        'phone' => 'Số điện thoại',
        'email' => 'Email',
        'system_type_id' => 'Hệ',
    ];

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
            'system_quote' => $this->systemType?->name_vi ?: $this->systemType?->name,
            default => null,
        };
    }

    public function getRequestPayloadFieldsAttribute(): array
    {
        $payload = $this->request_payload ?? [];

        if (isset($payload['fields']) && is_array($payload['fields'])) {
            return collect($payload['fields'])
                ->filter(fn (mixed $field): bool => is_array($field))
                ->map(function (array $field): array {
                    $key = (string) ($field['key'] ?? '');
                    $labelVi = (string) ($field['label_vi'] ?? static::friendlyPayloadKeyLabel($key));
                    $labelEn = (string) ($field['label_en'] ?? ($field['key'] ?? ''));
                    $rawValue = $field['value'] ?? null;

                    return [
                        'key' => $key,
                        'display_key' => $labelVi.($key !== '' ? ' ('.$key.')' : ''),
                        'label_vi' => $labelVi,
                        'label_en' => $labelEn,
                        'value' => static::formatPayloadFieldValue($key, $labelVi, $rawValue),
                        'input_type' => (string) ($field['input_type'] ?? 'text'),
                    ];
                })
                ->values()
                ->all();
        }

        return collect($payload)
            ->map(function (mixed $value, string $key): array {
                $labelVi = static::friendlyPayloadKeyLabel($key);

                return [
                    'key' => $key,
                    'display_key' => $labelVi.' ('.$key.')',
                    'label_vi' => $labelVi,
                    'label_en' => $key,
                    'value' => static::formatPayloadFieldValue($key, $labelVi, $value),
                    'input_type' => 'text',
                ];
            })
            ->values()
            ->all();
    }

    public function getRequestPayloadSummaryAttribute(): ?string
    {
        $fields = $this->request_payload_fields;

        if ($fields === []) {
            return null;
        }

        return collect($fields)
            ->map(fn (array $field): string => $field['label_vi'].': '.$field['value'])
            ->implode(' | ');
    }

    public function getRequestPayloadModeLabelAttribute(): ?string
    {
        $mode = data_get($this->request_payload, 'mode');

        if (! is_string($mode) || $mode === '') {
            return null;
        }

        return static::REQUEST_PAYLOAD_MODE_LABELS[$mode] ?? $mode;
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

    protected static function friendlyPayloadKeyLabel(string $key): string
    {
        return static::REQUEST_PAYLOAD_KEY_LABELS[$key] ?? $key;
    }

    protected static function formatPayloadFieldValue(string $key, string $labelVi, mixed $value): string
    {
        if (! is_scalar($value) && $value !== null) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        if ($value === null || $value === '') {
            return '';
        }

        if (static::shouldFormatAsMoney($key, $labelVi) && is_numeric($value)) {
            return number_format((float) $value, 0, ',', '.').' VNĐ';
        }

        return (string) $value;
    }

    protected static function shouldFormatAsMoney(string $key, string $labelVi): bool
    {
        $normalizedKey = strtolower(trim($key));
        $normalizedLabel = mb_strtolower(trim($labelVi));

        if (in_array($normalizedKey, static::REQUEST_PAYLOAD_MONEY_KEYS, true)) {
            return true;
        }

        return str_contains($normalizedLabel, 'tiền')
            || str_contains($normalizedLabel, 'chi phí')
            || str_contains($normalizedLabel, 'ngân sách');
    }
}
