<?php

namespace Database\Seeders;

use App\Models\Dealer;
use Illuminate\Database\Seeder;

class DealerSeeder extends Seeder
{
    public function run(): void
    {
        $dealers = [
            [
                'name' => 'Công ty Solar Việt',
                'code' => 'DLR1001',
                'phone' => '0901111111',
                'email' => 'solarviet@example.com',
                'address' => '12 Nguyễn Huệ, Quận 1, TP. Hồ Chí Minh',
                'status' => 'approved',
                'priority_order' => 1,
                'password' => 'secret123',
            ],
            [
                'name' => 'Điện mặt trời Sài Gòn',
                'code' => 'DLR1002',
                'phone' => '0902222222',
                'email' => 'saigon.solar@example.com',
                'address' => '88 Điện Biên Phủ, Bình Thạnh, TP. Hồ Chí Minh',
                'status' => 'approved',
                'priority_order' => 2,
                'password' => 'secret123',
            ],
            [
                'name' => 'Năng lượng xanh Hà Nội',
                'code' => 'DLR1003',
                'phone' => '0903333333',
                'email' => 'hanoi.green@example.com',
                'address' => '26 Cầu Giấy, Hà Nội',
                'status' => 'approved',
                'priority_order' => 3,
                'password' => 'secret123',
            ],
            [
                'name' => 'Đại lý Solar Đà Nẵng',
                'code' => 'DLR1004',
                'phone' => '0904444444',
                'email' => 'danang.solar@example.com',
                'address' => '45 Nguyễn Văn Linh, Đà Nẵng',
                'status' => 'approved',
                'priority_order' => 4,
                'password' => 'secret123',
            ],
            [
                'name' => 'Hợp tác xã Solar Mekong',
                'code' => 'DLR1005',
                'phone' => '0905555555',
                'email' => 'mekong.solar@example.com',
                'address' => '102 30/4, Ninh Kiều, Cần Thơ',
                'status' => 'approved',
                'priority_order' => 5,
                'password' => 'secret123',
            ],
        ];

        foreach ($dealers as $dealer) {
            Dealer::query()->updateOrCreate(
                ['email' => $dealer['email']],
                $dealer
            );
        }
    }
}
