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
                'name_vi' => 'Tấm pin',
                'name_en' => 'Solar Panel',
                'slug' => 'tam-pin',
                'sort_order' => 1,
                'children' => [],
            ],
            [
                'name_vi' => 'Lưu trữ',
                'name_en' => 'Storage',
                'slug' => 'luu-tru',
                'sort_order' => 2,
                'children' => [],
            ],
            [
                'name_vi' => 'Biến tần bơm',
                'name_en' => 'Pump Inverter',
                'slug' => 'bien-tan-bom',
                'sort_order' => 3,
                'children' => [],
            ],
            [
                'name_vi' => 'Inverter',
                'name_en' => 'Inverter',
                'slug' => 'inverter',
                'sort_order' => 4,
                'children' => [
                    ['name_vi' => 'Hybrid', 'name_en' => 'Hybrid', 'slug' => 'inverter-hybrid', 'sort_order' => 1],
                    ['name_vi' => 'On Grid', 'name_en' => 'On Grid', 'slug' => 'inverter-on-grid', 'sort_order' => 2],
                ],
            ],
        ];

        $allSlugs = collect($categories)
            ->flatMap(fn (array $category): array => [
                $category['slug'],
                ...collect($category['children'] ?? [])->pluck('slug')->all(),
            ])
            ->all();

        ProductCategory::query()->whereNotIn('slug', $allSlugs)->delete();

        foreach ($categories as $categoryData) {
            $category = ProductCategory::withTrashed()->updateOrCreate(
                ['slug' => $categoryData['slug']],
                [
                    'parent_id' => null,
                    'name' => $categoryData['name_vi'],
                    'name_vi' => $categoryData['name_vi'],
                    'name_en' => $categoryData['name_en'],
                    'is_active' => true,
                    'sort_order' => $categoryData['sort_order'],
                ]
            );

            if ($category->trashed()) {
                $category->restore();
            }

            foreach ($categoryData['children'] as $childData) {
                $child = ProductCategory::withTrashed()->updateOrCreate(
                    ['slug' => $childData['slug']],
                    [
                        'parent_id' => $category->id,
                        'name' => $childData['name_vi'],
                        'name_vi' => $childData['name_vi'],
                        'name_en' => $childData['name_en'],
                        'is_active' => true,
                        'sort_order' => $childData['sort_order'],
                    ]
                );

                if ($child->trashed()) {
                    $child->restore();
                }
            }

            $childSlugs = collect($categoryData['children'])->pluck('slug')->all();

            ProductCategory::query()
                ->where('parent_id', $category->id)
                ->when($childSlugs !== [], fn ($query) => $query->whereNotIn('slug', $childSlugs))
                ->when($childSlugs === [], fn ($query) => $query)
                ->delete();
        }
    }
}
