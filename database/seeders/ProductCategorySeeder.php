<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

class ProductCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Tấm pin năng lượng mặt trời',
                'slug' => 'tam-pin-nang-luong-mat-troi',
                'sort_order' => 1,
                'children' => [
                    ['name' => 'Panel Mono', 'slug' => 'panel-mono', 'sort_order' => 1],
                    ['name' => 'Panel N-Type', 'slug' => 'panel-n-type', 'sort_order' => 2],
                ],
            ],
            [
                'name' => 'Inverter',
                'slug' => 'inverter',
                'sort_order' => 2,
                'children' => [
                    ['name' => 'Inverter Hybrid', 'slug' => 'inverter-hybrid', 'sort_order' => 1],
                    ['name' => 'Inverter Hòa lưới', 'slug' => 'inverter-hoa-luoi', 'sort_order' => 2],
                ],
            ],
            [
                'name' => 'Phụ kiện',
                'slug' => 'phu-kien',
                'sort_order' => 3,
                'children' => [],
            ],
        ];

        foreach ($categories as $categoryData) {
            $category = ProductCategory::query()->updateOrCreate(
                ['slug' => $categoryData['slug']],
                [
                    'parent_id' => null,
                    'name' => $categoryData['name'],
                    'is_active' => true,
                    'sort_order' => $categoryData['sort_order'],
                ]
            );

            foreach ($categoryData['children'] as $childData) {
                ProductCategory::query()->updateOrCreate(
                    ['slug' => $childData['slug']],
                    [
                        'parent_id' => $category->id,
                        'name' => $childData['name'],
                        'is_active' => true,
                        'sort_order' => $childData['sort_order'],
                    ]
                );
            }
        }
    }
}
