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
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Post::truncate();
        \Illuminate\Support\Facades\DB::table('post_tag')->truncate();
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $adminId = \App\Models\AdminUser::first()->id ?? null;

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
                'title_2' => 'Xu hướng thị trường',
                'content_2' => 'Dự kiến số lượng các dự án lắp đặt sẽ tăng mạnh trong quý 3 năm nay...',
                'status' => 'published',
            ],
            [
                'title' => 'Cách chọn pin lưu trữ Lithium hiệu quả cho hệ thống Hybrid',
                'content' => 'Hệ thống năng lượng mặt trời Hybrid cần một bộ lưu trữ tin cậy. Pin Lithium với tuổi thọ cao và độ sâu xả (DoD) lớn đang dẫn đầu thị trường...',
                'title_2' => 'So sánh với Pin Acid Chì',
                'content_2' => 'Tuy có giá thành cao hơn nhưng về lâu dài Pin Lithium tối ưu chi phí hơn rất nhiều.',
                'status' => 'published',
            ],
            [
                'title' => 'Solar BigBang tham gia hội chợ công nghệ năng lượng tại TP.HCM',
                'content' => 'Trong tuần qua, đội ngũ Solar BigBang đã trình làng những giải pháp giám sát thông minh và các dòng Inverter thế hệ mới nhất...',
                'title_2' => 'Khách tham quan hứng thú',
                'content_2' => 'Rất nhiều tập đoàn lớn đã ghé thăm gian hàng và ký kết hợp tác trực tiếp tại sự kiện.',
                'status' => 'published',
            ],
            [
                'title' => 'Hướng dẫn vệ sinh tấm pin năng lượng mặt trời đúng cách',
                'content' => 'Hiệu suất của hệ thống có thể giảm tới 20% nếu tấm pin bị bám bụi bẩn. Việc vệ sinh định kỳ là vô cùng quan trọng...',
                'title_2' => 'Chu kỳ vệ sinh',
                'content_2' => 'Vào mùa khô, nên vệ sinh 3-4 tháng 1 lần. Đặc biệt lưu ý dùng nước mềm để tránh đóng cặn bề mặt kính.',
                'status' => 'published',
            ],
            [
                'title' => 'Chương trình khuyến mãi hè rực rỡ cùng Solar BigBang',
                'content' => 'Nhận ngay Voucher trị giá 5 triệu đồng khi lắp đặt hệ thống trên 5kWp trong tháng 6 này. Số lượng có hạn!',
                'title_2' => 'Thời gian áp dụng',
                'content_2' => 'Chương trình kéo dài từ 01/06/2026 đến hết 30/06/2026.',
                'status' => 'draft',
            ],
        ];

        foreach ($posts as $index => $postData) {
            $post = Post::create([
                'author_id' => $adminId,
                'title' => $postData['title'],
                'slug' => Str::slug($postData['title']),
                'content' => $postData['content'] . str_repeat("\r\n\r\nNội dung miêu tả chi tiết thêm cho bài báo này.", 3),
                'title_2' => $postData['title_2'],
                'content_2' => $postData['content_2'] . str_repeat("\r\n\r\nThông tin chi tiết kỹ thuật khác cần lưu ý.", 2),
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
