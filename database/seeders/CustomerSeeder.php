<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Dealer;
use App\Models\Province;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $dealers = Dealer::query()->orderBy('id')->get();

        if ($dealers->isEmpty()) {
            return;
        }

        $provinceIds = Province::query()
            ->whereNull('parent_id')
            ->pluck('id')
            ->values();

        $customers = [
            [
                'name' => 'Nguyễn Văn Hùng',
                'phone' => '0938000001',
                'email' => 'hung.dealer.customer@example.com',
                'address' => '168 Nguyễn Trãi, Quận 5, TP. Hồ Chí Minh',
                'status' => 'active',
                'lock_reason' => null,
            ],
            [
                'name' => 'Trần Thị Ngọc',
                'phone' => '0938000002',
                'email' => 'ngoc.dealer.customer@example.com',
                'address' => '15 Lê Lợi, Hải Châu, Đà Nẵng',
                'status' => 'active',
                'lock_reason' => null,
            ],
            [
                'name' => 'Lê Quốc Toàn',
                'phone' => '0938000003',
                'email' => 'toan.dealer.customer@example.com',
                'address' => '41 Trần Phú, Ninh Kiều, Cần Thơ',
                'status' => 'inactive',
                'lock_reason' => null,
            ],
            [
                'name' => 'Phạm Mỹ Dung',
                'phone' => '0938000004',
                'email' => 'dung.dealer.customer@example.com',
                'address' => '72 Cầu Giấy, Hà Nội',
                'status' => 'locked',
                'lock_reason' => 'Khách yêu cầu tạm khóa liên hệ.',
            ],
            [
                'name' => 'Đỗ Anh Khoa',
                'phone' => '0938000005',
                'email' => 'khoa.dealer.customer@example.com',
                'address' => '9 Hùng Vương, Thủ Dầu Một, Bình Dương',
                'status' => 'active',
                'lock_reason' => null,
            ],
        ];

        foreach ($customers as $index => $customer) {
            $dealerId = $dealers[$index % $dealers->count()]->id;
            $provinceId = $provinceIds->isNotEmpty()
                ? $provinceIds[$index % $provinceIds->count()]
                : null;

            Customer::query()->updateOrCreate(
                ['phone' => $customer['phone']],
                [
                    'dealer_id' => $dealerId,
                    'province_id' => $provinceId,
                    'name' => $customer['name'],
                    'email' => $customer['email'],
                    'address' => $customer['address'],
                    'status' => $customer['status'],
                    'lock_reason' => $customer['lock_reason'],
                ]
            );
        }
    }
}
