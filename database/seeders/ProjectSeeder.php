<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dealers = \App\Models\Dealer::all();
        $systemTypes = \App\Models\SystemType::all();

        if ($dealers->isEmpty() || $systemTypes->isEmpty()) {
            return;
        }

        $projectNames = [
            'Hệ thống điện mặt trời trên mái nhà anh Tuấn',
            'Dự án Solar 10kWp nhà phố Quận 7',
            'Lắp đặt năng lượng mặt trời xưởng mộc Bình Dương',
            'Dự án hoà lưới 15kWp - Long An',
            'Hệ thống lưu trữ Hybrid 5kWp - Đồng Nai',
            'Công trình nhà màng nông nghiệp Solar',
            'Lắp điện mặt trời cho quán Cafe',
            'Dự án mái nhà xưởng 50kWp kcn Tân Bình',
        ];

        foreach ($projectNames as $index => $name) {
            $status = ['pending', 'approved', 'rejected'][rand(0, 2)];
            $rejection_reason = $status === 'rejected' ? 'Hình ảnh công trình không rõ nét, thiếu hình ảnh inverter.' : null;
            $approved_by = $status === 'approved' ? 1 : null; // Giả sử admin_users ID 1
            
            \App\Models\Project::firstOrCreate(['title' => $name], [
                'dealer_id' => $dealers->random()->id,
                'system_type_id' => $systemTypes->random()->id,
                'address' => 'Địa chỉ giả lập số ' . rand(1, 100) . ', Thành phố Hồ Chí Minh',
                'description' => 'Mô tả chi tiết công trình: sử dụng tấm pin Tier 1, Inverter cao cấp, bảo hành 10 năm.',
                'capacity' => (rand(3, 50)) . ' kWp',
                'completion_date' => now()->subDays(rand(10, 100)),
                'status' => $status,
                'rejection_reason' => $rejection_reason,
                'approved_by' => $approved_by,
                'approved_at' => $status === 'approved' ? now()->subDays(rand(1, 30)) : null,
                'created_at' => now()->subDays(rand(1, 60)),
            ]);
        }
    }
}
