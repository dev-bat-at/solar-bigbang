<?php

namespace App\Services;

use App\Exceptions\QuoteCalculationException;
use App\Models\Product;
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

        if (! in_array($formulaType, ['bam_tai', 'hybrid', 'solar_pump'], true)) {
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

        if ($formulaType === 'bam_tai') {
            $dayRatio ??= $settings['day_ratio_default'] ?? 0.7;
            $nightRatio ??= max(0, min(1, 1 - $dayRatio));
        }

        $priceTier = null;
        $pricePerKw = null;

        if ($formulaType === 'hybrid') {
            $calculation = $this->calculateHybrid(
                monthlyBill: $monthlyBill,
                phaseType: $phaseType,
                settings: $settings,
            );

            $grossKwp = (float) $calculation['gross_kwp'];
            $installedKwp = (float) $calculation['recommended_kwp'];
        } else {
            $grossKwp = match ($formulaType) {
                'bam_tai' => $monthlyBill / $settings['electric_price'] / $settings['yield'],
                'solar_pump' => $monthlyBill / $settings['electric_price'] / $settings['yield'],
                default => throw new QuoteCalculationException('Loại công thức chưa được hỗ trợ.'),
            };

            $grossKwp = round($grossKwp, 2);
            $installedKwp = $this->resolveInstalledKwp(
                formulaType: $formulaType,
                grossKwp: $grossKwp,
                dayRatio: $dayRatio,
            );

            $priceTier = $this->resolvePriceTier($systemType->quote_price_tiers ?? [], $phaseType, $installedKwp);

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
                    grossKwp: $grossKwp,
                    installedKwp: $installedKwp,
                    pricePerKw: $pricePerKw,
                    settings: $settings,
                ),
                'solar_pump' => $this->calculateSolarPump(
                    monthlyBill: $monthlyBill,
                    recommendedKwp: $installedKwp,
                    pricePerKw: $pricePerKw,
                    settings: $settings,
                ),
                default => throw new QuoteCalculationException('Loại công thức chưa được hỗ trợ.'),
            };
        }

        $relatedProducts = $this->resolveRelatedProducts(
            $phaseType,
            $installedKwp,
        );

        return [
            'system_type' => [
                'id' => $systemType->id,
                'slug' => $systemType->slug,
                'name' => $systemType->name_vi ?: $systemType->name,
                'quote_enabled' => (bool) $systemType->quote_is_active,
                'formula_type' => $formulaType,
                'vi' => [
                    'name' => $systemType->name_vi ?: $systemType->name,
                    'description' => $systemType->description_vi ?: $systemType->description,
                ],
                'en' => [
                    'name' => $systemType->name_en ?: $systemType->name_vi ?: $systemType->name,
                    'description' => $systemType->description_en ?: $systemType->description_vi ?: $systemType->description,
                ],
            ],
            // 'input' => [
            //     'phase_type' => $phaseType,
            //     'monthly_bill' => $monthlyBill,
            //     'day_ratio' => round($dayRatio, 4),
            //     'night_ratio' => round($nightRatio, 4),
            // ],
            'result' => [
                'gross_kwp' => $grossKwp,
                'installed_kwp' => $installedKwp,
                'recommended_kwp' => $installedKwp,
                'price_per_kw' => $pricePerKw !== null ? round($pricePerKw) : null,
                'investment_cost' => round($calculation['investment_cost']),
                'estimated_monthly_saving' => round($calculation['estimated_monthly_saving']),
                'battery_capacity' => isset($calculation['battery_capacity']) ? round($calculation['battery_capacity'], 1) : null,
                'battery_price' => isset($calculation['battery_price']) ? round($calculation['battery_price']) : null,
                'solar_price' => isset($calculation['solar_price']) ? round($calculation['solar_price']) : null,
                'bill_multiplier' => isset($calculation['bill_multiplier']) ? (float) $calculation['bill_multiplier'] : null,
                'phase_price_factor' => isset($calculation['phase_price_factor']) ? (float) $calculation['phase_price_factor'] : null,
                'phase_kw_factor' => isset($calculation['phase_kw_factor']) ? (float) $calculation['phase_kw_factor'] : null,
            ],
            'related_products' => $relatedProducts,
            // 'breakdown' => [
            //     'settings' => $settings,
            //     'matched_price_tier' => $priceTier,
            // ],
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

    protected function resolveInstalledKwp(string $formulaType, float $grossKwp, float $dayRatio): float
    {
        $installedKwp = match ($formulaType) {
            'bam_tai' => $grossKwp * $dayRatio,
            'hybrid', 'solar_pump' => $grossKwp,
            default => $grossKwp,
        };

        return round($installedKwp, 1);
    }

    protected function resolveSettings(SystemType $systemType, ?float $dayRatio): array
    {
        $settings = $systemType->quote_settings ?? [];

        $defaults = match ($systemType->quote_formula_type) {
            'bam_tai' => [
                'electric_price' => 2200,
                'yield' => 120,
                'market_factor' => 1,
                'day_ratio_default' => $dayRatio ?? 0.7,
                'saving_factor' => 1,
            ],
            'hybrid' => [
                'electric_price' => 2500,
                'yield' => 120,
                'market_factor' => 1,
                'three_phase_price_factor' => 1.1,
                'three_phase_kw_factor' => 0.91,
                'bill_multiplier_tiers' => SystemType::defaultHybridBillMultiplierTiers(),
                'saving_factor' => 1,
            ],
            'solar_pump' => [
                'electric_price' => 2200,
                'yield' => 140,
                'market_factor' => 1,
                'saving_factor' => 1,
            ],
            default => [],
        };

        $resolved = [
            ...$defaults,
            ...array_filter($settings, fn (mixed $value) => $value !== null && $value !== ''),
        ];

        if (isset($resolved['day_ratio_default'])) {
            $resolved['day_ratio_default'] = $this->normalizeRatio($resolved['day_ratio_default']) ?? 0.7;
        }

        return $resolved;
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

    protected function resolveRelatedProducts(string $phaseType, float $recommendedKwp): array
    {
        $maxDistanceKw = max(2.0, round($recommendedKwp * 0.35, 2));

        return Product::query()
            ->with(['productCategory', 'productSubcategory'])
            ->where('status', 'published')
            ->get()
            ->map(function (Product $product) use ($phaseType, $recommendedKwp): ?array {
                $powerKw = $this->normalizeProductPowerToKw($product->power);

                if ($powerKw === null) {
                    return null;
                }

                if (! $this->productMatchesPhaseType($product, $phaseType)) {
                    return null;
                }

                return [
                    'id' => $product->id,
                    'code' => $product->code,
                    'slug' => $product->slug,
                    'name_vi' => $product->name_vi,
                    'name_en' => $product->name_en,
                    'power' => $product->power,
                    'power_kw' => $powerKw,
                    'distance_kw' => round(abs($powerKw - $recommendedKwp), 2),
                    'price' => $product->price,
                    'is_price_contact' => (bool) $product->is_price_contact,
                    'status' => $product->status,
                    'primary_image' => $this->resolvePrimaryProductImage($product),
                    'category' => [
                        'id' => $product->productCategory?->id,
                        'name_vi' => $product->productCategory?->name_vi,
                        'name_en' => $product->productCategory?->name_en,
                        'slug' => $product->productCategory?->slug,
                    ],
                    'subcategory' => [
                        'id' => $product->productSubcategory?->id,
                        'name_vi' => $product->productSubcategory?->name_vi,
                        'name_en' => $product->productSubcategory?->name_en,
                        'slug' => $product->productSubcategory?->slug,
                    ],
                ];
            })
            ->filter()
            ->filter(fn (array $product): bool => $product['distance_kw'] <= $maxDistanceKw)
            ->sortBy([
                ['distance_kw', 'asc'],
                ['power_kw', 'asc'],
            ])
            ->values()
            ->take(6)
            ->all();
    }

    protected function normalizeProductPowerToKw(mixed $power): ?float
    {
        if (! is_string($power) || trim($power) === '') {
            return null;
        }

        $normalized = strtolower(str_replace(',', '.', trim($power)));

        if (! preg_match('/(\d+(?:\.\d+)?)/', $normalized, $matches)) {
            return null;
        }

        $value = (float) $matches[1];

        if (str_contains($normalized, 'kwp') || str_contains($normalized, 'kw')) {
            return round($value, 2);
        }

        if (str_contains($normalized, 'wp') || str_contains($normalized, 'w')) {
            return round($value / 1000, 2);
        }

        return $value > 100 ? round($value / 1000, 2) : round($value, 2);
    }

    protected function productMatchesPhaseType(Product $product, string $phaseType): bool
    {
        $categorySlug = strtolower((string) ($product->productCategory?->slug ?? ''));
        $subcategorySlug = strtolower((string) ($product->productSubcategory?->slug ?? ''));
        $haystack = strtolower(implode(' ', array_filter([
            $categorySlug,
            $subcategorySlug,
            (string) $product->name_vi,
            (string) $product->name_en,
            (string) $product->tagline_vi,
            (string) $product->tagline_en,
            (string) $product->power,
        ])));

        if ($phaseType === '1P') {
            return ! str_contains($haystack, '3 pha') && ! str_contains($haystack, '3phase') && ! str_contains($haystack, '3-phase');
        }

        if ($phaseType === '3P') {
            if (str_contains($haystack, '1 pha') || str_contains($haystack, '1phase') || str_contains($haystack, '1-phase')) {
                return false;
            }

            return true;
        }

        return true;
    }

    protected function resolvePrimaryProductImage(Product $product): ?string
    {
        $images = is_array($product->images) ? $product->images : [];

        if ($images === []) {
            return null;
        }

        $firstImage = $images[0];

        if (! is_string($firstImage) || $firstImage === '') {
            return null;
        }

        return str_starts_with($firstImage, 'http://') || str_starts_with($firstImage, 'https://')
            ? $firstImage
            : asset($firstImage);
    }

    protected function calculateLoadFollowing(
        float $monthlyBill,
        float $dayRatio,
        float $grossKwp,
        float $installedKwp,
        float $pricePerKw,
        array $settings,
    ): array {
        $marketFactor = (float) ($settings['market_factor'] ?? 1);
        $savingFactor = (float) ($settings['saving_factor'] ?? 1);

        $investmentCost = $installedKwp * $pricePerKw * $marketFactor;

        $estimatedMonthlySaving = $monthlyBill * $dayRatio * $savingFactor;

        return [
            'gross_kwp' => $grossKwp,
            'installed_kwp' => $installedKwp,
            'investment_cost' => $investmentCost,
            'estimated_monthly_saving' => $estimatedMonthlySaving,
        ];
    }

    protected function calculateHybrid(
        float $monthlyBill,
        string $phaseType,
        array $settings,
    ): array {
        $marketFactor = (float) ($settings['market_factor'] ?? 1);
        $savingFactor = (float) ($settings['saving_factor'] ?? 1);
        $billMultiplierTier = $this->resolveBillMultiplierTier($settings['bill_multiplier_tiers'] ?? [], $monthlyBill);

        if ($billMultiplierTier === null) {
            throw new QuoteCalculationException('Chưa có mốc tiền điện phù hợp cho hệ lưu trữ.');
        }

        $billMultiplier = (float) ($billMultiplierTier['multiplier'] ?? 0);

        if ($billMultiplier <= 0) {
            throw new QuoteCalculationException('Hệ số mốc tiền điện của hệ lưu trữ chưa hợp lệ.');
        }

        $phasePriceFactor = $phaseType === '3P'
            ? (float) ($settings['three_phase_price_factor'] ?? 1.1)
            : 1.0;
        $phaseKwFactor = $phaseType === '3P'
            ? (float) ($settings['three_phase_kw_factor'] ?? 0.91)
            : 1.0;

        $investmentCost = $monthlyBill * $billMultiplier * $phasePriceFactor * $marketFactor;
        $recommendedKwp = $investmentCost / $billMultiplier / (float) $settings['electric_price'] / (float) $settings['yield'] * $phaseKwFactor;
        $recommendedKwp = round($recommendedKwp, 2);
        $estimatedMonthlySaving = $monthlyBill * $savingFactor;

        return [
            'gross_kwp' => $recommendedKwp,
            'recommended_kwp' => round($recommendedKwp, 1),
            'bill_multiplier' => $billMultiplier,
            'phase_price_factor' => $phasePriceFactor,
            'phase_kw_factor' => $phaseKwFactor,
            'investment_cost' => $investmentCost,
            'estimated_monthly_saving' => $estimatedMonthlySaving,
        ];
    }

    protected function resolveBillMultiplierTier(array $tiers, float $monthlyBill): ?array
    {
        return collect($tiers)
            ->filter(function (array $tier) use ($monthlyBill): bool {
                $minBill = (float) ($tier['min_bill'] ?? 0);
                $maxBill = $tier['max_bill'] ?? null;

                return $monthlyBill >= $minBill
                    && (($maxBill === null) || ($maxBill === '') || ($monthlyBill <= (float) $maxBill));
            })
            ->sortBy(fn (array $tier): float => (float) ($tier['min_bill'] ?? 0))
            ->first();
    }

    protected function calculateSolarPump(
        float $monthlyBill,
        float $recommendedKwp,
        float $pricePerKw,
        array $settings,
    ): array {
        $marketFactor = (float) ($settings['market_factor'] ?? 1);
        $savingFactor = (float) ($settings['saving_factor'] ?? 1);

        $investmentCost = $recommendedKwp * $pricePerKw * $marketFactor;
        $estimatedMonthlySaving = $monthlyBill * $savingFactor;

        return [
            'investment_cost' => $investmentCost,
            'estimated_monthly_saving' => $estimatedMonthlySaving,
        ];
    }
}
