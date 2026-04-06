<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SamplePostSeeder extends Seeder
{
    public function run(): void
    {
        $tags = [
            'Năng lượng mặt trời',
            'Pin Lithium',
            'Biến tần Inverter',
            'Tin tức thị trường',
            'Hướng dẫn lắp đặt',
            'Công nghệ mới',
            'Khuyến mãi',
            'Dự án tiêu biểu',
        ];

        $createdTags = collect($tags)->map(function ($name) {
            return Tag::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name]
            );
        });

        $posts = [
            [
                'title' => 'Tương lai của năng lượng tái tạo tại Việt Nam 2024',
                'content' => 'Năng lượng mặt trời đang trở thành lựa chọn hàng đầu cho các doanh nghiệp và hộ gia đình tại Việt Nam. Với các chính sách hỗ trợ phát triển năng lượng mái nhà, chúng ta đang chứng kiến sự bùng nổ của ngành này...',
                'status' => 'published',
            ],
            [
                'title' => 'Cách chọn pin lưu trữ Lithium hiệu quả cho hệ thống Hybrid',
                'content' => 'Hệ thống năng lượng mặt trời Hybrid cần một bộ lưu trữ tin cậy. Pin Lithium với tuổi thọ cao và độ sâu xả (DoD) lớn đang dẫn đầu thị trường...',
                'status' => 'published',
            ],
            [
                'title' => 'Solar BigBang tham gia hội chợ công nghệ năng lượng tại TP.HCM',
                'content' => 'Trong tuần qua, đội ngũ Solar BigBang đã trình làng những giải pháp giám sát thông minh và các dòng Inverter thế hệ mới nhất...',
                'status' => 'published',
            ],
            [
                'title' => 'Hướng dẫn vệ sinh tấm pin năng lượng mặt trời đúng cách',
                'content' => 'Hiệu suất của hệ thống có thể giảm tới 20% nếu tấm pin bị bám bụi bẩn. Việc vệ sinh định kỳ là vô cùng quan trọng...',
                'status' => 'published',
            ],
            [
                'title' => 'Chương trình khuyến mãi hè rực rỡ cùng Solar BigBang',
                'content' => 'Nhận ngay Voucher trị giá 5 triệu đồng khi lắp đặt hệ thống trên 5kWp trong tháng 6 này. Số lượng có hạn!',
                'status' => 'draft',
            ],
        ];

        foreach ($posts as $index => $postData) {
            $post = Post::create([
                'title' => $postData['title'],
                'slug' => Str::slug($postData['title']),
                'content' => $postData['content'] . str_repeat("\r\n\r\nNội dung bổ sung để bài viết có vẻ dài hơn và thực tế hơn cho việc kiểm tra giao diện quản trị Admin Filament.", 5),
                'featured_image' => 'posts/thumbnails/sample-' . ($index + 1) . '.jpg',
                'status' => $postData['status'],
                'publish_at' => now()->subDays($index * 2),
                'seo_title' => $postData['title'] . ' | Solar BigBang',
                'seo_description' => 'Tìm hiểu thêm về ' . $postData['title'] . ' tại website chính thức của Solar BigBang. Tin tức cập nhật mới nhất.',
                'seo_keywords' => 'năng lượng mặt trời, solar bigbang, ' . strtolower($postData['title']),
            ]);

            // Gán 1-3 tag ngẫu nhiên
            $post->tags()->attach(
                $createdTags->random(rand(1, 3))->pluck('id')->toArray()
            );
        }
    }
}
