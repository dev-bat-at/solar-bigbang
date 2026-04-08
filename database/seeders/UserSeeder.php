<?php

namespace Database\Seeders;

use App\Models\Province;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $provinceIds = Province::query()
            ->whereNull('parent_id')
            ->pluck('id')
            ->values();

        $users = [
            [
                'name' => 'Nguyễn Minh Anh',
                'phone' => '0911000001',
                'email' => 'minhanh.user@example.com',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Trần Quốc Bảo',
                'phone' => '0911000002',
                'email' => 'quocbao.user@example.com',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Lê Thu Cúc',
                'phone' => '0911000003',
                'email' => 'thucuc.user@example.com',
                'email_verified_at' => null,
            ],
            [
                'name' => 'Phạm Gia Duy',
                'phone' => '0911000004',
                'email' => 'giaduy.user@example.com',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Hoàng Khánh Linh',
                'phone' => '0911000005',
                'email' => 'khanhlinh.user@example.com',
                'email_verified_at' => null,
            ],
        ];

        foreach ($users as $index => $user) {
            $provinceId = $provinceIds->isNotEmpty()
                ? $provinceIds[$index % $provinceIds->count()]
                : null;

            User::query()->updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'phone' => $user['phone'],
                    'province_id' => $provinceId,
                    'email_verified_at' => $user['email_verified_at'],
                    'password' => Hash::make('password'),
                ]
            );
        }
    }
}
