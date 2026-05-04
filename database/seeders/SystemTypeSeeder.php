<?php

namespace Database\Seeders;

use App\Models\SystemType;
use Illuminate\Database\Seeder;

class SystemTypeSeeder extends Seeder
{
    public function run(): void
    {
        $systems = [
            [
                'slug' => 'hybrid',
                'name_vi' => 'Hệ Hybrid',
                'name_en' => 'Hybrid System',
                'description_vi' => 'Lưu trữ điện mặt trời cho ban đêm',
                'description_en' => 'Store solar power for nighttime use.',
                'quote_formula_type' => 'hybrid',
                'quote_is_active' => true,
                'quote_settings' => [
                    'electric_price' => 2500,
                    'yield' => 120,
                    'market_factor' => 1,
                    'three_phase_price_factor' => 1.1,
                    'three_phase_kw_factor' => 0.91,
                    'saving_factor' => 1,
                    'bill_multiplier_tiers' => SystemType::defaultHybridBillMultiplierTiers(),
                ],
                'quote_price_tiers' => [
                    ['phase_type' => 'ALL', 'min_kw' => 0, 'max_kw' => 5, 'price_per_kw' => 18500000],
                    ['phase_type' => 'ALL', 'min_kw' => 5, 'max_kw' => 10, 'price_per_kw' => 17800000],
                    ['phase_type' => 'ALL', 'min_kw' => 10, 'max_kw' => null, 'price_per_kw' => 17100000],
                ],
                'quote_recommendations' => [
                    ['phase_type' => 'ALL', 'min_kw' => 0, 'max_kw' => 5, 'panel_model' => 'Jinko 550W', 'panel_watt' => 550, 'inverter_model' => 'Hybrid 5kW', 'inverter_kw' => 5, 'battery_model' => 'LFP 5kWh', 'battery_kwh' => 5, 'note' => 'Cau hinh hybrid co ban.'],
                    ['phase_type' => 'ALL', 'min_kw' => 5, 'max_kw' => 10, 'panel_model' => 'Jinko 550W', 'panel_watt' => 550, 'inverter_model' => 'Hybrid 8kW', 'inverter_kw' => 8, 'battery_model' => 'LFP 10kWh', 'battery_kwh' => 10, 'note' => 'Cau hinh hybrid cho gia dinh dung nhieu buoi toi.'],
                ],
            ],
            [
                'slug' => 'hoa-luoi-bam-tai',
                'name_vi' => 'Hệ hòa lưới',
                'name_en' => 'On-grid Solar System',
                'description_vi' => 'Tính công suất theo tiền điện, tỷ lệ dùng điện ban ngày và loại điện 1 pha hoặc 3 pha.',
                'description_en' => 'Estimate on-grid solar size from monthly bill, daytime usage ratio, and 1-phase or 3-phase power.',
                'quote_formula_type' => 'bam_tai',
                'quote_is_active' => true,
                'quote_settings' => [
                    'electric_price' => 2200,
                    'yield' => 120,
                    'market_factor' => 1,
                    'day_ratio_default' => 0.7,
                    'saving_factor' => 1,
                ],
                'quote_price_tiers' => [
                    ['phase_type' => '1P', 'min_kw' => 0, 'max_kw' => 10, 'price_per_kw' => 7000000],
                    ['phase_type' => '1P', 'min_kw' => 10, 'max_kw' => null, 'price_per_kw' => 6800000],
                    ['phase_type' => '3P', 'min_kw' => 0, 'max_kw' => 10, 'price_per_kw' => 6800000],
                    ['phase_type' => '3P', 'min_kw' => 10, 'max_kw' => 30, 'price_per_kw' => 6200000],
                    ['phase_type' => '3P', 'min_kw' => 30, 'max_kw' => null, 'price_per_kw' => 5700000],
                ],
                'quote_recommendations' => [
                    ['phase_type' => '1P', 'package_name' => 'Gói hòa lưới 1 pha 3kW', 'target_kw' => 3.0, 'min_kw' => 0, 'max_kw' => 4.0, 'panel_model' => 'Jinko 550W', 'panel_watt' => 550, 'inverter_model' => 'On Grid 3kW', 'inverter_kw' => 3, 'note' => 'Phù hợp hộ gia đình dùng điện ban ngày thấp.'],
                    ['phase_type' => '1P', 'package_name' => 'Gói hòa lưới 1 pha 5kW', 'target_kw' => 5.0, 'min_kw' => 4.0, 'max_kw' => 5.25, 'panel_model' => 'Jinko 550W', 'panel_watt' => 550, 'inverter_model' => 'On Grid 5kW', 'inverter_kw' => 5, 'note' => 'Phù hợp gia đình có hóa đơn điện trung bình.'],
                    ['phase_type' => '1P', 'package_name' => 'Gói hòa lưới 1 pha 5.5kW', 'target_kw' => 5.5, 'min_kw' => 5.25, 'max_kw' => 5.75, 'panel_model' => 'Jinko 550W', 'panel_watt' => 550, 'inverter_model' => 'On Grid 5kW', 'inverter_kw' => 5, 'note' => 'Phù hợp nhu cầu gần mức 5.3kW.'],
                    ['phase_type' => '1P', 'package_name' => 'Gói hòa lưới 1 pha 6kW', 'target_kw' => 6.0, 'min_kw' => 5.75, 'max_kw' => 7.0, 'panel_model' => 'Jinko 550W', 'panel_watt' => 550, 'inverter_model' => 'On Grid 6kW', 'inverter_kw' => 6, 'note' => 'Phù hợp hộ kinh doanh nhỏ hoặc nhà dùng điện cao ban ngày.'],
                    ['phase_type' => '1P', 'package_name' => 'Gói hòa lưới 1 pha 8kW', 'target_kw' => 8.0, 'min_kw' => 7.0, 'max_kw' => 10.0, 'panel_model' => 'Jinko 550W', 'panel_watt' => 550, 'inverter_model' => 'On Grid 8kW', 'inverter_kw' => 8, 'note' => 'Phù hợp gia đình lớn hoặc biệt thự.'],
                    ['phase_type' => '3P', 'package_name' => 'Gói hòa lưới 3 pha 10kW', 'target_kw' => 10.0, 'min_kw' => 8.0, 'max_kw' => 12.0, 'panel_model' => 'Jinko 550W', 'panel_watt' => 550, 'inverter_model' => 'On Grid 10kW 3 pha', 'inverter_kw' => 10, 'note' => 'Phù hợp cửa hàng, văn phòng nhỏ.'],
                    ['phase_type' => '3P', 'package_name' => 'Gói hòa lưới 3 pha 15kW', 'target_kw' => 15.0, 'min_kw' => 12.0, 'max_kw' => 18.0, 'panel_model' => 'Jinko 550W', 'panel_watt' => 550, 'inverter_model' => 'On Grid 15kW 3 pha', 'inverter_kw' => 15, 'note' => 'Phù hợp nhà xưởng nhỏ hoặc khách sạn mini.'],
                    ['phase_type' => '3P', 'package_name' => 'Gói hòa lưới 3 pha 20kW', 'target_kw' => 20.0, 'min_kw' => 18.0, 'max_kw' => 25.0, 'panel_model' => 'Jinko 550W', 'panel_watt' => 550, 'inverter_model' => 'On Grid 20kW 3 pha', 'inverter_kw' => 20, 'note' => 'Phù hợp xưởng sản xuất mức vừa.'],
                    ['phase_type' => '3P', 'package_name' => 'Gói hòa lưới 3 pha 30kW', 'target_kw' => 30.0, 'min_kw' => 25.0, 'max_kw' => 35.0, 'panel_model' => 'Jinko 550W', 'panel_watt' => 550, 'inverter_model' => 'On Grid 30kW 3 pha', 'inverter_kw' => 30, 'note' => 'Phù hợp xưởng sản xuất lớn hơn.'],
                ],
            ],
            [
                'slug' => 'solar-pump',
                'name_vi' => 'Solar Pump',
                'name_en' => 'Solar Pump',
                'description_vi' => 'Bơm nước nông nghiệp trực tiếp',
                'description_en' => 'Direct solar pumping for agriculture.',
                'quote_formula_type' => 'solar_pump',
                'quote_is_active' => true,
                'quote_settings' => [
                    'electric_price' => 2200,
                    'yield' => 140,
                    'market_factor' => 1,
                    'saving_factor' => 1,
                ],
                'quote_price_tiers' => [
                    ['phase_type' => 'ALL', 'min_kw' => 0, 'max_kw' => 3, 'price_per_kw' => 14500000],
                    ['phase_type' => 'ALL', 'min_kw' => 3, 'max_kw' => 7, 'price_per_kw' => 13900000],
                    ['phase_type' => 'ALL', 'min_kw' => 7, 'max_kw' => null, 'price_per_kw' => 13200000],
                ],
                'quote_recommendations' => [
                    ['phase_type' => 'ALL', 'min_kw' => 0, 'max_kw' => 3, 'panel_model' => 'Jinko 550W', 'panel_watt' => 550, 'inverter_model' => 'Pump Inverter 3kW', 'inverter_kw' => 3, 'note' => 'Phu hop bom tuoi nho.'],
                    ['phase_type' => 'ALL', 'min_kw' => 3, 'max_kw' => 7, 'panel_model' => 'Jinko 550W', 'panel_watt' => 550, 'inverter_model' => 'Pump Inverter 5.5kW', 'inverter_kw' => 5.5, 'note' => 'Phu hop nhu cau tuoi tieu trang trai.'],
                ],
            ],
            [
                'slug' => 'chua-biet-can-tu-van',
                'name_vi' => 'Chưa biết, cần tư vấn',
                'name_en' => 'Not Sure, Need Consultation',
                'description_vi' => 'Khách hàng chưa xác định loại hệ phù hợp và cần tư vấn.',
                'description_en' => 'The customer has not decided on a suitable system and needs consultation.',
                'quote_formula_type' => null,
                'quote_is_active' => false,
                'quote_settings' => null,
                'quote_price_tiers' => null,
                'quote_recommendations' => null,
            ],
        ];

        $slugs = collect($systems)->pluck('slug')->all();

        SystemType::query()->whereNotIn('slug', $slugs)->delete();

        foreach ($systems as $system) {
            $record = SystemType::withTrashed()->updateOrCreate(
                ['slug' => $system['slug']],
                $system + ['name' => $system['name_vi'], 'description' => $system['description_vi']]
            );

            if ($record->trashed()) {
                $record->restore();
            }
        }
    }
}
