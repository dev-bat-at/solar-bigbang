<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ImportLocationDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $provincesJson = public_path('data/provinces.json');
        $subdivisionsJson = public_path('data/subdivisions.json');

        if (!file_exists($provincesJson) || !file_exists($subdivisionsJson)) {
            return;
        }

        $provinces = json_decode(file_get_contents($provincesJson), true);
        $subdivisions = json_decode(file_get_contents($subdivisionsJson), true);

        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        \App\Models\Province::truncate();
        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();

        \Illuminate\Support\Facades\DB::transaction(function () use ($provinces, $subdivisions) {
            // 1. Import Provinces
            $provinceMap = [];
            foreach ($provinces as $p) {
                $record = \App\Models\Province::create([
                    'id' => $p['id'],
                    'name' => $p['name'],
                    'code' => $p['code'],
                    'type' => 'Tỉnh/Thành',
                    'is_active' => $p['is_active'] ?? true,
                ]);
                $provinceMap[$p['name']] = $record->id;
            }

            // 2. Import Subdivisions (Districts/Wards)
            // Note: subdivisions.json is large, we should chunk if possible or just use batch insert
            $chunks = array_chunk($subdivisions, 1000);
            foreach ($chunks as $chunk) {
                $data = [];
                foreach ($chunk as $s) {
                    $parentId = $provinceMap[$s['parent_name']] ?? null;
                    if (!$parentId)
                        continue;

                    $data[] = [
                        'id' => $s['id'],
                        'name' => $s['name'],
                        'code' => 'SUB_' . $s['id'],
                        'parent_id' => $parentId,
                        'type' => $s['division_type'] ?? $s['level'] ?? 'Quận/Huyện',
                        'is_active' => $s['is_active'] ?? true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                \App\Models\Province::insert($data);
            }

            \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();
        });
    }
}
