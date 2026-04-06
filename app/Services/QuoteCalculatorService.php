<?php

namespace App\Services;

use App\Exceptions\QuoteCalculationException;
use App\Models\SystemType;

class QuoteCalculatorService
{
    public function calculate(SystemType | int | string $systemType, array $payload): array
    {
        $systemType = $this->resolveSystemType($systemType);

        if (! $systemType->quote_is_active) {
            throw new QuoteCalculationException('Hệ này chưa bật cấu hình báo giá.');
        }

        $formulaType = $systemType->quote_formula_type;

        if (! in_array($formulaType, ['bam_tai', 'hybrid'], true)) {
            throw new QuoteCalculationException('Hệ chưa có loại công thức hỗ trợ.');
        }

        $phaseType = $this->normalizePhaseType($payload['phase_type'] ?? null);
        $monthlyBill = $this->normalizeMoney($payload['monthly_bill'] ?? 0);
        $dayRatio = $this->normalizeRatio($payload['day_ratio'] ?? null);
        $nightRatio = $this->normalizeRatio($payload['night_ratio'] ?? null);

        if ($monthlyBill <= 0) {
            throw new QuoteCalculationException('Tiền điện trung bình tháng phải lớn hơn 0.');
        }

        if ($phaseType === null) {
            throw new QuoteCalculationException('Loại điện sử dụng chưa hợp lệ.');
        }

        if (($dayRatio === null) && ($nightRatio !== null)) {
            $dayRatio = max(0, min(1, 1 - $nightRatio));
        }

        if (($nightRatio === null) && ($dayRatio !== null)) {
            $nightRatio = max(0, min(1, 1 - $dayRatio));
        }

        $settings = $this->resolveSettings($systemType, $dayRatio);
        $dayRatio ??= $settings['day_ratio_default'];
        $nightRatio ??= max(0, min(1, 1 - $dayRatio));

        $recommendedKwp = match ($formulaType) {
            'bam_tai' => $monthlyBill / $settings['electric_price'] / $settings['yield'],
            'hybrid' => $monthlyBill / $settings['electric_price'] / $settings['yield'],
            default => throw new QuoteCalculationException('Loại công thức chưa được hỗ trợ.'),
        };

        $recommendedKwp = round($recommendedKwp, 1);

        $priceTier = $this->resolvePriceTier($systemType->quote_price_tiers ?? [], $phaseType, $recommendedKwp);

        if (! $priceTier) {
            throw new QuoteCalculationException('Chưa có đơn giá theo mốc kWp phù hợp cho hệ và loại điện này.');
        }

        $pricePerKw = (float) ($priceTier['price_per_kw'] ?? 0);

        if ($pricePerKw <= 0) {
            throw new QuoteCalculationException('Đơn giá theo mốc kWp chưa hợp lệ.');
        }

        $calculation = match ($formulaType) {
            'bam_tai' => $this->calculateLoadFollowing(
                monthlyBill: $monthlyBill,
                dayRatio: $dayRatio,
                recommendedKwp: $recommendedKwp,
                pricePerKw: $pricePerKw,
                settings: $settings,
            ),
            'hybrid' => $this->calculateHybrid(
                monthlyBill: $monthlyBill,
                dayRatio: $dayRatio,
                recommendedKwp: $recommendedKwp,
                pricePerKw: $pricePerKw,
                settings: $settings,
            ),
        };

        $recommendation = $this->resolveRecommendation(
            $systemType->quote_recommendations ?? [],
            $phaseType,
            $recommendedKwp,
            $calculation['battery_capacity'] ?? null,
        );

        return [
            'system_type' => [
                'id' => $systemType->id,
                'name' => $systemType->name,
                'slug' => $systemType->slug,
                'formula_type' => $formulaType,
            ],
            'input' => [
                'phase_type' => $phaseType,
                'monthly_bill' => $monthlyBill,
                'day_ratio' => round($dayRatio, 4),
                'night_ratio' => round($nightRatio, 4),
            ],
            'result' => [
                'recommended_kwp' => $recommendedKwp,
                'price_per_kw' => round($pricePerKw),
                'investment_cost' => round($calculation['investment_cost']),
                'estimated_monthly_saving' => round($calculation['estimated_monthly_saving']),
                'battery_capacity' => isset($calculation['battery_capacity']) ? round($calculation['battery_capacity'], 1) : null,
                'battery_price' => isset($calculation['battery_price']) ? round($calculation['battery_price']) : null,
                'solar_price' => isset($calculation['solar_price']) ? round($calculation['solar_price']) : null,
            ],
            'suggestion' => $recommendation,
            'breakdown' => [
                'settings' => $settings,
                'matched_price_tier' => $priceTier,
            ],
        ];
    }

    protected function resolveSystemType(SystemType | int | string $systemType): SystemType
    {
        if ($systemType instanceof SystemType) {
            return $systemType;
        }

        $query = SystemType::query();

        $resolved = is_numeric($systemType)
            ? $query->find($systemType)
            : $query->where('slug', $systemType)->first();

        if (! $resolved) {
            throw new QuoteCalculationException('Không tìm thấy hệ để tính báo giá.');
        }

        return $resolved;
    }

    protected function normalizePhaseType(mixed $phaseType): ?string
    {
        if (! is_string($phaseType)) {
            return null;
        }

        $phaseType = strtoupper(trim($phaseType));

        return in_array($phaseType, ['1P', '3P'], true) ? $phaseType : null;
    }

    protected function normalizeMoney(mixed $value): float
    {
        if (is_string($value)) {
            $value = str_replace([',', ' '], '', $value);
        }

        return (float) $value;
    }

    protected function normalizeRatio(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = (float) $value;

        if ($value > 1) {
            $value /= 100;
        }

        return max(0, min(1, $value));
    }

    protected function resolveSettings(SystemType $systemType, ?float $dayRatio): array
    {
        $settings = $systemType->quote_settings ?? [];

        $defaults = match ($systemType->quote_formula_type) {
            'bam_tai' => [
                'electric_price' => 2500,
                'yield' => 120,
                'market_factor' => 1,
                'k_factor' => 1,
                'day_ratio_default' => $dayRatio ?? 0.6,
                'saving_factor' => 1,
            ],
            'hybrid' => [
                'electric_price' => 2800,
                'yield' => 135,
                'market_factor' => 1,
                'battery_price_per_kwh' => 2500000,
                'backup_hours' => 1,
                'day_ratio_default' => 0.5,
                'saving_factor' => 1,
            ],
            default => [],
        };

        return [
            ...$defaults,
            ...array_filter($settings, fn (mixed $value) => $value !== null && $value !== ''),
        ];
    }

    protected function resolvePriceTier(array $tiers, string $phaseType, float $recommendedKwp): ?array
    {
        $matchedTier = collect($tiers)
            ->filter(function (array $tier) use ($phaseType, $recommendedKwp): bool {
                $tierPhase = strtoupper((string) ($tier['phase_type'] ?? 'ALL'));
                $minKw = (float) ($tier['min_kw'] ?? 0);
                $maxKw = $tier['max_kw'] ?? null;

                return in_array($tierPhase, [$phaseType, 'ALL'], true)
                    && ($recommendedKwp >= $minKw)
                    && (($maxKw === null) || ($maxKw === '') || ($recommendedKwp < (float) $maxKw));
            })
            ->sortBy(fn (array $tier): float => (float) ($tier['min_kw'] ?? 0))
            ->last();

        return $matchedTier ? array_merge($matchedTier, [
            'phase_type' => strtoupper((string) ($matchedTier['phase_type'] ?? 'ALL')),
        ]) : null;
    }

    protected function resolveRecommendation(array $recommendations, string $phaseType, float $recommendedKwp, ?float $batteryCapacity): array
    {
        $matched = collect($recommendations)
            ->filter(function (array $recommendation) use ($phaseType, $recommendedKwp): bool {
                $itemPhase = strtoupper((string) ($recommendation['phase_type'] ?? 'ALL'));
                $minKw = (float) ($recommendation['min_kw'] ?? 0);
                $maxKw = $recommendation['max_kw'] ?? null;

                return in_array($itemPhase, [$phaseType, 'ALL'], true)
                    && ($recommendedKwp >= $minKw)
                    && (($maxKw === null) || ($maxKw === '') || ($recommendedKwp < (float) $maxKw));
            })
            ->sortBy(fn (array $recommendation): float => (float) ($recommendation['min_kw'] ?? 0))
            ->last();

        if (! $matched) {
            return [
                'panel_model' => null,
                'panel_watt' => null,
                'panel_count' => null,
                'inverter_model' => null,
                'inverter_kw' => null,
                'battery_model' => null,
                'battery_kwh' => $batteryCapacity ? round($batteryCapacity, 1) : null,
                'note' => null,
            ];
        }

        $panelWatt = filled($matched['panel_watt'] ?? null) ? (float) $matched['panel_watt'] : null;
        $panelCount = filled($matched['panel_count'] ?? null)
            ? (int) $matched['panel_count']
            : (($panelWatt && $panelWatt > 0) ? (int) ceil(($recommendedKwp * 1000) / $panelWatt) : null);

        return [
            'panel_model' => $matched['panel_model'] ?? null,
            'panel_watt' => $panelWatt,
            'panel_count' => $panelCount,
            'inverter_model' => $matched['inverter_model'] ?? null,
            'inverter_kw' => filled($matched['inverter_kw'] ?? null) ? (float) $matched['inverter_kw'] : null,
            'battery_model' => $matched['battery_model'] ?? null,
            'battery_kwh' => filled($matched['battery_kwh'] ?? null)
                ? (float) $matched['battery_kwh']
                : ($batteryCapacity ? round($batteryCapacity, 1) : null),
            'note' => $matched['note'] ?? null,
        ];
    }

    protected function calculateLoadFollowing(
        float $monthlyBill,
        float $dayRatio,
        float $recommendedKwp,
        float $pricePerKw,
        array $settings,
    ): array {
        $marketFactor = (float) ($settings['market_factor'] ?? 1);
        $kFactor = (float) ($settings['k_factor'] ?? 1);
        $savingFactor = (float) ($settings['saving_factor'] ?? 1);

        $investmentCost = $recommendedKwp
            * $dayRatio
            * $pricePerKw
            * (1 + (($dayRatio - 0.5) * $kFactor))
            * $marketFactor;

        $estimatedMonthlySaving = $monthlyBill * $dayRatio * $savingFactor;

        return [
            'investment_cost' => $investmentCost,
            'estimated_monthly_saving' => $estimatedMonthlySaving,
        ];
    }

    protected function calculateHybrid(
        float $monthlyBill,
        float $dayRatio,
        float $recommendedKwp,
        float $pricePerKw,
        array $settings,
    ): array {
        $marketFactor = (float) ($settings['market_factor'] ?? 1);
        $batteryPricePerKwh = (float) ($settings['battery_price_per_kwh'] ?? 0);
        $backupHours = (float) ($settings['backup_hours'] ?? 1);
        $dayRatioDefault = (float) ($settings['day_ratio_default'] ?? 0.5);
        $effectiveDayRatio = max($dayRatioDefault, $dayRatio);
        $savingFactor = (float) ($settings['saving_factor'] ?? 1);

        $solarPrice = $recommendedKwp * $effectiveDayRatio * $pricePerKw * $marketFactor;
        $dailyConsumptionKwh = ($monthlyBill / (float) $settings['electric_price']) / 30;
        $batteryCapacity = $dailyConsumptionKwh * $backupHours;
        $batteryPrice = $batteryCapacity * $batteryPricePerKwh;
        $estimatedMonthlySaving = $monthlyBill * min(1, ($effectiveDayRatio + 0.15)) * $savingFactor;

        return [
            'solar_price' => $solarPrice,
            'battery_capacity' => $batteryCapacity,
            'battery_price' => $batteryPrice,
            'investment_cost' => $solarPrice + $batteryPrice,
            'estimated_monthly_saving' => $estimatedMonthlySaving,
        ];
    }
}
