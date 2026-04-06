<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            [
                'code' => 'SP-JINKO-550',
                'name_vi' => 'Tấm pin năng lượng mặt trời Jinko Solar 550W',
                'name_en' => 'Jinko Solar Panel 550W',
                'slug' => 'tam-pin-nang-luong-mat-troi-jinko-solar-550w',
                'tagline_vi' => 'Công nghệ N-Type hiệu suất cao, bảo hành 25 năm',
                'tagline_en' => 'High efficiency N-Type technology, 25-year warranty',
                'status' => 'published',
                'is_best_seller' => true,
                'images' => json_encode([
                    'https://jinkosolar.com.vn/wp-content/uploads/2022/10/tiger-neo-72hl4-v.png',
                    'https://jinkosolar.com.vn/wp-content/uploads/2022/10/tiger-neo-60hl4-v-2.png'
                ]),
                'price' => 3200000,
                'price_unit_vi' => 'VNĐ / Tấm',
                'price_unit_en' => 'VND / Panel',
                'power' => '550Wp',
                'efficiency' => '21.3%',
                'warranty' => '25 năm',
                'description_vi' => '<h2>Tấm Pin Jinko 550Wp N-Type</h2><p>Tấm pin năng lượng mặt trời Jinko 550Wp sử dụng công nghệ N-Type tiên tiến, đem lại hiệu suất vượt trội và tổn hao năng lượng cực thấp. Đây là sự lựa chọn hàng đầu cho các dự án điện mặt trời dân dụng và công nghiệp.</p>',
                'description_en' => '<h2>Jinko 550Wp N-Type Solar Panel</h2><p>Jinko 550Wp solar panel uses advanced N-Type technology, providing superior efficiency and extremely low energy loss. It is the top choice for residential and commercial solar projects.</p>',
                'specifications' => json_encode([
                    [
                        'label_vi' => 'Loại Cell',
                        'label_en' => 'Cell Type',
                        'value_vi' => 'N-Type Monocrystalline',
                        'value_en' => 'N-Type Monocrystalline'
                    ],
                    [
                        'label_vi' => 'Số lượng Cell',
                        'label_en' => 'No. of Cells',
                        'value_vi' => '144 (6×24)',
                        'value_en' => '144 (6×24)'
                    ],
                    [
                        'label_vi' => 'Kích thước',
                        'label_en' => 'Dimensions',
                        'value_vi' => '2278×1134×35mm',
                        'value_en' => '2278×1134×35mm'
                    ],
                    [
                        'label_vi' => 'Trọng lượng',
                        'label_en' => 'Weight',
                        'value_vi' => '28 kg',
                        'value_en' => '28 kg'
                    ]
                ]),
                'documents' => json_encode([]),
                'faqs' => json_encode([
                    [
                        'question_vi' => 'Tấm pin này chịu lực tốt không?',
                        'question_en' => 'Is this panel durable?',
                        'answer_vi' => 'Tấm pin có khả năng chịu tải trọng tuyết lên đến 5400Pa và tải trọng gió lên đến 2400Pa.',
                        'answer_en' => 'The panel can withstand snow loads up to 5400Pa and wind loads up to 2400Pa.'
                    ],
                    [
                        'question_vi' => 'Bảo hành như thế nào?',
                        'question_en' => 'How is the warranty?',
                        'answer_vi' => 'Bảo hành vật lý 12 năm và bảo hành hiệu suất tuyến tính 25 năm.',
                        'answer_en' => '12-year product warranty and 25-year linear power warranty.'
                    ]
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'INV-LUX-6K',
                'name_vi' => 'Inverter Hybrid Luxpower 6kW',
                'name_en' => 'Luxpower 6kW Hybrid Inverter',
                'slug' => 'inverter-hybrid-luxpower-6kw',
                'tagline_vi' => 'Biến tần thông minh, hỗ trợ đa chế độ chạy',
                'tagline_en' => 'Smart inverter, supports multiple operating modes',
                'status' => 'published',
                'is_best_seller' => true,
                'images' => json_encode([
                    'https://luxpowertek.com/wp-content/uploads/2021/04/lpx.png'
                ]),
                'price' => 25000000,
                'price_unit_vi' => 'VNĐ / Bộ',
                'price_unit_en' => 'VND / Set',
                'power' => '6kW',
                'efficiency' => '97.5%',
                'warranty' => '5 năm',
                'description_vi' => '<h2>Biến tần Hybrid Luxpower 6kW</h2><p>Dòng biến tần Hybrid mạnh mẽ, quản lý năng lượng thông minh, cho phép ưu tiên sử dụng năng lượng mặt trời, sạc pin hoặc đẩy lưới linh hoạt.</p>',
                'description_en' => '<h2>Luxpower 6kW Hybrid Inverter</h2><p>Powerful Hybrid inverter series, smart energy management, allows priority use of solar energy, battery charging or grid export flexibly.</p>',
                'specifications' => json_encode([
                    [
                        'label_vi' => 'Công suất danh định',
                        'label_en' => 'Nominal Power',
                        'value_vi' => '6000W',
                        'value_en' => '6000W'
                    ],
                    [
                        'label_vi' => 'Số cổng mPPT',
                        'label_en' => 'MPPT Trackers',
                        'value_vi' => '2 cổng (độc lập)',
                        'value_en' => '2 (independent)'
                    ],
                    [
                        'label_vi' => 'Giao tiếp hệ thống',
                        'label_en' => 'Communication',
                        'value_vi' => 'RS485, CAN, Wi-Fi',
                        'value_en' => 'RS485, CAN, Wi-Fi'
                    ],
                    [
                        'label_vi' => 'Chống nước',
                        'label_en' => 'IP Rating',
                        'value_vi' => 'IP65',
                        'value_en' => 'IP65'
                    ]
                ]),
                'documents' => json_encode([]),
                'faqs' => json_encode([
                    [
                        'question_vi' => 'Sản phẩm có tương thích với pin Lithium nào?',
                        'question_en' => 'Which Lithium batteries are compatible?',
                        'answer_vi' => 'Luxpower tương thích với hơn 95% các thương hiệu pin Lithium trên thị trường như Pylontech, Deye, Giga,...',
                        'answer_en' => 'Luxpower is compatible with over 95% of Lithium battery brands on the market such as Pylontech, Deye, Giga,...'
                    ]
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];

        DB::table('products')->insert($products);
    }
}
