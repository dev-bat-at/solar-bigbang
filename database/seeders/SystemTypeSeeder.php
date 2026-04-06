<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SystemTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            'Hòa lưới bám tải',
            'Hòa lưới lưu trữ (Hybrid)',
            'Độc lập (Off-grid)',
            'Bơm nước năng lượng mặt trời',
            'Hệ thống nước nóng mặt trời'
        ];

        foreach ($types as $type) {
            \App\Models\SystemType::firstOrCreate([
                'slug' => \Illuminate\Support\Str::slug($type),
            ], [
                'name' => $type,
            ]);
        }
    }
}
