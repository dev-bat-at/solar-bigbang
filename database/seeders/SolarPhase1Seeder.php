<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SolarPhase1Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Seed Dealers (Names in Vietnamese)
        $dealerNames = ['Công ty Solar Việt', 'Điện mặt trời Sài Gòn', 'Năng lượng xanh Hà Nội', 'Đại lý Solar Đà Nẵng', 'Hợp tác xã Solar MeKong'];
        foreach ($dealerNames as $name) {
            \App\Models\Dealer::create([
                'name' => $name,
                'code' => 'DLR' . rand(1000, 9999),
                'phone' => '09' . rand(11111111, 99999999),
                'email' => str_replace(' ', '', strtolower($name)) . '@example.com',
                'address' => 'Số ' . rand(1, 100) . ' Đường ABC, Quận XYZ',
                'status' => 'approved',
                'priority_order' => rand(1, 10),
            ]);
        }

        // 2. Seed Customers (thuộc về đại lý)
        $dealers = \App\Models\Dealer::all();
        $customerNames = ['Nguyễn Văn A', 'Trần Thị B', 'Lê Văn C', 'Phạm Minh D', 'Hoàng Anh E'];
        foreach ($customerNames as $name) {
            \App\Models\Customer::create([
                'name' => $name,
                'dealer_id' => $dealers->random()->id,
                'phone' => '09' . rand(11111111, 99999999),
                'email' => str_replace(' ', '', strtolower($name)) . '@gmail.com',
                'address' => 'Số ' . rand(1, 200) . ' Đường ' . ['Nguyễn Huệ', 'Lê Lợi', 'Trần Hưng Đạo', 'Hai Bà Trưng', 'Pasteur'][rand(0, 4)],
                'status' => 'active',
            ]);
        }

        // 3. Seed Leads
        $customers = \App\Models\Customer::all();
        $provinces = ['Hồ Chí Minh', 'Hà Nội', 'Đà Nẵng', 'Cần Thơ', 'Bình Dương'];

        for ($i = 0; $i < 20; $i++) {
            \App\Models\Lead::create([
                'code' => 'L' . date('Ymd') . strtoupper(\Illuminate\Support\Str::random(4)),
                'customer_id' => $customers->random()->id,
                'dealer_id' => $dealers->random()->id,
                'status' => ['new', 'contacting', 'won', 'lost'][rand(0, 3)],
                'source' => 'Facebook Ads',
                'province_name' => $provinces[rand(0, count($provinces) - 1)],
                'estimated_value' => rand(50000000, 200000000),
                'assigned_at' => now()->subDays(rand(1, 10)),
            ]);
        }
    }
}
