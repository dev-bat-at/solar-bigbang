<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Str;

class SystemType extends Model
{
    use SoftDeletes, LogsActivity;

    protected $guarded = [];

    protected const DEFAULT_STANDARD_QUOTE_FIELDS = [
        [
            'key' => 'monthly_bill',
            'label_vi' => 'Tiền điện trung bình tháng',
            'label_en' => 'Average monthly electricity bill',
            'placeholder_vi' => 'Nhập tiền điện trung bình tháng',
            'placeholder_en' => 'Enter average monthly electricity bill',
            'input_type' => 'number',
            'required' => true,
            'is_default' => true,
        ],
        [
            'key' => 'phase_type',
            'label_vi' => 'Loại điện',
            'label_en' => 'Power phase',
            'placeholder_vi' => 'Chọn 1P hoặc 3P',
            'placeholder_en' => 'Choose 1P or 3P',
            'input_type' => 'select',
            'required' => true,
            'is_default' => true,
            'options' => [
                ['value' => '1P', 'label_vi' => '1 pha', 'label_en' => '1 phase'],
                ['value' => '3P', 'label_vi' => '3 pha', 'label_en' => '3 phases'],
            ],
        ],
    ];

    protected const DEFAULT_HYBRID_BILL_MULTIPLIER_TIERS = [
        ['min_bill' => 1500000, 'max_bill' => 3000000, 'multiplier' => 52],
        ['min_bill' => 3500000, 'max_bill' => 5000000, 'multiplier' => 48],
        ['min_bill' => 5500000, 'max_bill' => 8000000, 'multiplier' => 44],
        ['min_bill' => 8500000, 'max_bill' => 20000000, 'multiplier' => 42],
        ['min_bill' => 21000000, 'max_bill' => 100000000, 'multiplier' => 40],
    ];

    protected const DEFAULT_QUOTE_RATIO_FIELDS = [
        [
            'key' => 'start_day',
            'label_vi' => 'Tỷ lệ điện ban ngày (%)',
            'label_en' => 'Daytime power ratio (%)',
            'placeholder_vi' => 'Ví dụ 70',
            'placeholder_en' => 'Example 70',
            'input_type' => 'number',
            'required' => true,
            'is_default' => false,
            'default_value' => 70,
            'aliases' => ['day_ratio'],
        ],
        [
            'key' => 'end_night',
            'label_vi' => 'Tỷ lệ điện ban đêm (%)',
            'label_en' => 'Nighttime power ratio (%)',
            'placeholder_vi' => 'Ví dụ 30',
            'placeholder_en' => 'Example 30',
            'input_type' => 'number',
            'required' => true,
            'is_default' => false,
            'default_value' => 30,
            'aliases' => ['night_ratio'],
        ],
    ];

    protected function casts(): array
    {
        return [
            'quote_is_active' => 'boolean',
            'show_calculation_formula' => 'boolean',
            'quote_settings' => 'array',
            'quote_price_tiers' => 'array',
            'quote_recommendations' => 'array',
            'quote_request_fields' => 'array',
        ];
    }

    protected static function boot()
    {
        parent::boot();
        static::saving(function ($model) {
            $model->name = $model->name_vi ?: $model->name;
            $model->description = $model->description_vi ?: $model->description;

            $quoteSettings = $model->quote_settings ?? [];

            $ratioFields = collect(data_get($quoteSettings, 'ratio_fields', []))
                ->filter(fn (mixed $field): bool => is_array($field))
                ->values();

            if ($ratioFields->isNotEmpty()) {
                $dayDefault = data_get($ratioFields->first(), 'default_value');

                if ($dayDefault !== null && $dayDefault !== '') {
                    data_set($quoteSettings, 'day_ratio_default', $dayDefault);
                }
            }

            $model->quote_settings = $quoteSettings;
        });
        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name_vi ?: $model->name);
            }
        });
        static::updating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name_vi ?: $model->name);
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    public static function defaultQuoteRatioFields(): array
    {
        return static::DEFAULT_QUOTE_RATIO_FIELDS;
    }

    public static function defaultStandardQuoteFields(): array
    {
        return static::DEFAULT_STANDARD_QUOTE_FIELDS;
    }

    public static function defaultHybridBillMultiplierTiers(): array
    {
        return static::DEFAULT_HYBRID_BILL_MULTIPLIER_TIERS;
    }

    public function getQuoteInputModeAttribute(): string
    {
        return $this->show_calculation_formula ? 'custom_fields' : 'day_night_ratio';
    }

    public function getNormalizedQuoteRequestFieldsAttribute(): array
    {
        return collect($this->quote_request_fields ?? [])
            ->filter(fn (mixed $field): bool => is_array($field))
            ->map(function (array $field): array {
                $key = Str::of((string) ($field['key'] ?? ''))
                    ->trim()
                    ->snake()
                    ->lower()
                    ->value();

                return [
                    'key' => $key,
                    'label_vi' => trim((string) ($field['label_vi'] ?? '')),
                    'label_en' => trim((string) ($field['label_en'] ?? '')),
                    'placeholder_vi' => trim((string) ($field['placeholder_vi'] ?? '')),
                    'placeholder_en' => trim((string) ($field['placeholder_en'] ?? '')),
                    'input_type' => static::normalizeInputType($field['input_type'] ?? null),
                    'required' => (bool) ($field['required'] ?? false),
                    'is_default' => false,
                ];
            })
            ->filter(fn (array $field): bool => $field['key'] !== '' && $field['label_vi'] !== '' && $field['label_en'] !== '')
            ->values()
            ->all();
    }

    public function getQuoteRatioFieldsAttribute(): array
    {
        $dayRatioDefault = $this->normalizeRatioPercentage(
            data_get($this->quote_settings, 'ratio_fields.0.default_value', data_get($this->quote_settings, 'day_ratio_default', 70))
        );
        $nightRatioDefault = max(0, min(100, round(100 - $dayRatioDefault, 2)));

        $configuredFields = collect(data_get($this->quote_settings, 'ratio_fields', static::defaultQuoteRatioFields()))
            ->filter(fn (mixed $field): bool => is_array($field))
            ->map(function (array $field): array {
                $key = Str::of((string) ($field['key'] ?? ''))
                    ->trim()
                    ->snake()
                    ->lower()
                    ->value();

                $aliases = collect($field['aliases'] ?? [])
                    ->filter(fn (mixed $alias): bool => is_string($alias) && trim($alias) !== '')
                    ->map(fn (string $alias): string => Str::of($alias)->trim()->snake()->lower()->value())
                    ->values()
                    ->all();

                return [
                    'key' => $key,
                    'label_vi' => trim((string) ($field['label_vi'] ?? '')),
                    'label_en' => trim((string) ($field['label_en'] ?? '')),
                    'placeholder_vi' => trim((string) ($field['placeholder_vi'] ?? '')),
                    'placeholder_en' => trim((string) ($field['placeholder_en'] ?? '')),
                    'input_type' => 'number',
                    'required' => true,
                    'is_default' => false,
                    'default_value' => $field['default_value'] ?? null,
                    'aliases' => $aliases,
                ];
            })
            ->filter(fn (array $field): bool => $field['key'] !== '' && $field['label_vi'] !== '' && $field['label_en'] !== '')
            ->values();

        if ($configuredFields->count() !== 2) {
            $configuredFields = collect(static::defaultQuoteRatioFields());
        }

        return $configuredFields
            ->values()
            ->map(function (array $field, int $index) use ($dayRatioDefault, $nightRatioDefault): array {
                $fallbackValue = $index === 0 ? $dayRatioDefault : $nightRatioDefault;
                $field['default_value'] = $this->normalizeRatioPercentage($field['default_value'] ?? $fallbackValue);

                return $field;
            })
            ->all();
    }

    public function getQuoteFormFieldsAttribute(): array
    {
        if (! $this->quote_is_active) {
            return [];
        }

        if ($this->show_calculation_formula) {
            return $this->normalized_quote_request_fields;
        }

        $standardFields = static::defaultStandardQuoteFields();

        return match ($this->quote_formula_type) {
            'bam_tai' => [
                ...$standardFields,
                ...$this->quote_ratio_fields,
            ],
            'hybrid' => $standardFields,
            'solar_pump' => [
                $standardFields[0],
            ],
            default => $standardFields,
        };
    }

    public function getQuoteFormulaContentViAttribute(): ?string
    {
        return data_get($this->quote_settings, 'formula_content_vi');
    }

    public function getQuoteFormulaContentEnAttribute(): ?string
    {
        return data_get($this->quote_settings, 'formula_content_en');
    }

    protected static function normalizeInputType(mixed $type): string
    {
        $type = strtolower(trim((string) $type));

        return in_array($type, ['text', 'number', 'textarea'], true) ? $type : 'text';
    }

    protected function normalizeRatioPercentage(mixed $value): float
    {
        $value = (float) $value;

        if ($value <= 1) {
            $value *= 100;
        }

        return round(max(0, min(100, $value)), 2);
    }
}
