<?php

namespace Tests\Feature\Api;

use App\Models\Dealer;
use App\Models\Post;
use App\Models\Product;
use App\Models\Province;
use App\Models\SystemSetting;
use App\Models\SystemType;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicApiTest extends TestCase
{
    use RefreshDatabase;

    protected function apiHeaders(array $headers = []): array
    {
        return array_merge([
            config('api_auth.header', 'X-API-KEY') => config('api_auth.key', 'abcxyz'),
            'Accept' => 'application/json',
        ], $headers);
    }

    public function test_api_requires_api_key_header(): void
    {
        $this->getJson('/api/config/contact')
            ->assertUnauthorized()
            ->assertJsonPath('success', false);
    }

    public function test_can_get_contact_config(): void
    {
        SystemSetting::set('contact_phone', '0909000001');
        SystemSetting::set('contact_zalo_link', 'https://zalo.me/0909000001');
        SystemSetting::set('contact_email', 'contact@example.com');
        SystemSetting::set('contact_business_hours', '08:00 - 17:30 - Thứ 2 đến Thứ 7');

        $this->withHeaders($this->apiHeaders())
            ->getJson('/api/config/contact')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.phone', '0909000001')
            ->assertJsonPath('data.zalo_link', 'https://zalo.me/0909000001')
            ->assertJsonPath('data.email', 'contact@example.com')
            ->assertJsonPath('data.business_hours', '08:00 - 17:30 - Thứ 2 đến Thứ 7');
    }

    public function test_can_list_and_show_provinces(): void
    {
        $province = Province::query()->create([
            'code' => 'HCM',
            'name' => 'Ho Chi Minh',
            'type' => 'Tỉnh/Thành',
            'is_active' => true,
        ]);

        Province::query()->create([
            'code' => 'Q1',
            'name' => 'Quan 1',
            'type' => 'Quan/Huyen',
            'parent_id' => $province->id,
            'is_active' => true,
        ]);

        $this->withHeaders($this->apiHeaders())
            ->getJson('/api/provinces')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.children_count', 1);

        $this->withHeaders($this->apiHeaders())
            ->getJson("/api/provinces/{$province->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $province->id)
            ->assertJsonPath('data.code', 'HCM');
    }

    public function test_can_register_user(): void
    {
        $province = Province::query()->create([
            'code' => 'DN',
            'name' => 'Da Nang',
            'type' => 'Tỉnh/Thành',
            'is_active' => true,
        ]);

        $payload = [
            'phone' => '0911000010',
            'email' => 'new.user@example.com',
            'password' => 'secret123',
            'province_id' => $province->id,
        ];

        $this->withHeaders($this->apiHeaders())
            ->postJson('/api/auth/register', $payload)
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.phone', '0911000010')
            ->assertJsonPath('data.email', 'new.user@example.com')
            ->assertJsonPath('data.province.id', $province->id);

        $this->assertDatabaseHas('users', [
            'phone' => '0911000010',
            'email' => 'new.user@example.com',
            'province_id' => $province->id,
        ]);
    }

    public function test_can_login_with_phone_and_get_authenticated_profile(): void
    {
        $province = Province::query()->create([
            'code' => 'HN',
            'name' => 'Ha Noi',
            'type' => 'Tỉnh/Thành',
            'is_active' => true,
        ]);

        $user = User::query()->create([
            'name' => 'Test User',
            'phone' => '0911222333',
            'email' => 'test.user@example.com',
            'province_id' => $province->id,
            'password' => 'secret123',
        ]);

        $loginResponse = $this->withHeaders($this->apiHeaders())
            ->postJson('/api/auth/login', [
                'login' => $user->phone,
                'password' => 'secret123',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.phone', $user->phone);

        $token = $loginResponse->json('data.access_token');

        $this->withHeaders($this->apiHeaders([
            'Authorization' => 'Bearer '.$token,
        ]))
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_can_logout_and_revoke_current_token(): void
    {
        $province = Province::query()->create([
            'code' => 'CT',
            'name' => 'Can Tho',
            'type' => 'Tỉnh/Thành',
            'is_active' => true,
        ]);

        $user = User::query()->create([
            'name' => 'Logout User',
            'phone' => '0911333444',
            'email' => 'logout.user@example.com',
            'province_id' => $province->id,
            'password' => 'secret123',
        ]);

        $loginResponse = $this->withHeaders($this->apiHeaders())
            ->postJson('/api/auth/login', [
                'login' => $user->email,
                'password' => 'secret123',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $token = $loginResponse->json('data.access_token');

        $this->withHeaders($this->apiHeaders([
            'Authorization' => 'Bearer '.$token,
        ]))
            ->postJson('/api/auth/logout')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message_vn', 'Đăng xuất thành công.')
            ->assertJsonPath('message_en', 'Logged out successfully.');

        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->withHeaders($this->apiHeaders([
            'Authorization' => 'Bearer '.$token,
        ]))
            ->getJson('/api/auth/me')
            ->assertUnauthorized()
            ->assertJsonPath('success', false);
    }

    public function test_user_can_soft_delete_own_account(): void
    {
        $province = Province::query()->create([
            'code' => 'PY',
            'name' => 'Phu Yen',
            'type' => 'Tỉnh/Thành',
            'is_active' => true,
        ]);

        $user = User::query()->create([
            'name' => 'Delete User',
            'phone' => '0911777888',
            'email' => 'delete.user@example.com',
            'province_id' => $province->id,
            'password' => 'secret123',
        ]);

        $token = $user->createToken(config('api_auth.token_name', 'mobile-app'))->plainTextToken;

        $this->withHeaders($this->apiHeaders([
            'Authorization' => 'Bearer '.$token,
        ]))
            ->deleteJson('/api/auth/account')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message_vn', 'Xóa tài khoản thành công.')
            ->assertJsonPath('message_en', 'Account deleted successfully.');

        $this->assertSoftDeleted('users', [
            'id' => $user->id,
        ]);

        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->withHeaders($this->apiHeaders([
            'Authorization' => 'Bearer '.$token,
        ]))
            ->getJson('/api/auth/me')
            ->assertUnauthorized()
            ->assertJsonPath('success', false);
    }

    public function test_dealer_can_update_own_avatar(): void
    {
        Storage::fake('root_public');

        $dealer = Dealer::query()->create([
            'name' => 'Dealer Avatar',
            'code' => 'DLR-001',
            'phone' => '0900000001',
            'email' => 'dealer.avatar@example.com',
            'password' => 'secret123',
            'status' => 'approved',
        ]);

        $token = $dealer->createToken(config('api_auth.token_name', 'mobile-app'))->plainTextToken;

        $response = $this->withHeaders($this->apiHeaders([
            'Authorization' => 'Bearer '.$token,
        ]))
            ->post('/api/dealers/me/avatar', [
                'avatar' => UploadedFile::fake()->image('dealer-avatar.jpg', 300, 300),
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $dealer->id)
            ->assertJsonPath('message_vn', 'Cập nhật ảnh đại lý thành công.')
            ->assertJsonPath('message_en', 'Dealer avatar updated successfully.');

        $dealer->refresh();

        $this->assertNotNull($dealer->avatar);
        $this->assertStringStartsWith('dealers/avatars/', $dealer->avatar);
        Storage::disk('root_public')->assertExists($dealer->avatar);

        $this->assertStringContainsString('/dealers/avatars/', $response->json('data.avatar'));
    }

    public function test_can_calculate_on_grid_quote_with_closest_package_suggestions(): void
    {
        $province = Province::query()->create([
            'code' => 'BD',
            'name' => 'Binh Duong',
            'type' => 'Tỉnh/Thành',
            'is_active' => true,
        ]);

        $user = User::query()->create([
            'name' => 'Quote User',
            'phone' => '0911444555',
            'email' => 'quote.user@example.com',
            'province_id' => $province->id,
            'password' => 'secret123',
        ]);

        $token = $user->createToken(config('api_auth.token_name', 'mobile-app'))->plainTextToken;

        $systemType = SystemType::query()->create([
            'name' => 'Hệ hòa lưới',
            'name_vi' => 'Hệ hòa lưới',
            'name_en' => 'On-grid Solar System',
            'slug' => 'hoa-luoi-bam-tai',
            'description' => 'Hệ hòa lưới',
            'description_vi' => 'Hệ hòa lưới',
            'description_en' => 'On-grid Solar System',
            'quote_formula_type' => 'bam_tai',
            'quote_is_active' => true,
            'quote_settings' => [
                'electric_price' => 2200,
                'yield' => 120,
                'market_factor' => 1,
                'day_ratio_default' => 70,
                'saving_factor' => 1,
            ],
            'quote_price_tiers' => [
                ['phase_type' => '1P', 'min_kw' => 0, 'max_kw' => 10, 'price_per_kw' => 7000000],
                ['phase_type' => '1P', 'min_kw' => 10, 'max_kw' => null, 'price_per_kw' => 6800000],
            ],
            'quote_recommendations' => [],
        ]);

        Product::query()->create([
            'code' => 'INV-ONGRID-5K',
            'slug' => 'inverter-on-grid-5kw',
            'name_vi' => 'Inverter On Grid 5kW',
            'name_en' => 'On Grid Inverter 5kW',
            'status' => 'published',
            'price' => 12000000,
            'power' => '5kW',
            'efficiency' => '97%',
            'warranty_vi' => '5 năm',
            'warranty_en' => '5 years',
            'price_unit_vi' => 'VNĐ / Bộ',
            'price_unit_en' => 'VND / Set',
            'description_vi' => 'Demo',
            'description_en' => 'Demo',
        ]);

        Product::query()->create([
            'code' => 'INV-ONGRID-55K',
            'slug' => 'inverter-on-grid-5-5kw',
            'name_vi' => 'Inverter On Grid 5.5kW',
            'name_en' => 'On Grid Inverter 5.5kW',
            'status' => 'published',
            'price' => 12800000,
            'power' => '5.5kW',
            'efficiency' => '97%',
            'warranty_vi' => '5 năm',
            'warranty_en' => '5 years',
            'price_unit_vi' => 'VNĐ / Bộ',
            'price_unit_en' => 'VND / Set',
            'description_vi' => 'Demo',
            'description_en' => 'Demo',
        ]);

        Product::query()->create([
            'code' => 'INV-ONGRID-6K',
            'slug' => 'inverter-on-grid-6kw',
            'name_vi' => 'Inverter On Grid 6kW',
            'name_en' => 'On Grid Inverter 6kW',
            'status' => 'published',
            'price' => 13500000,
            'power' => '6kW',
            'efficiency' => '97%',
            'warranty_vi' => '5 năm',
            'warranty_en' => '5 years',
            'price_unit_vi' => 'VNĐ / Bộ',
            'price_unit_en' => 'VND / Set',
            'description_vi' => 'Demo',
            'description_en' => 'Demo',
        ]);

        $this->withHeaders($this->apiHeaders([
            'Authorization' => 'Bearer '.$token,
        ]))
            ->postJson('/api/quote/calculate', [
                'system_type_id' => $systemType->id,
                'phase_type' => '1P',
                'monthly_bill' => 2000000,
                'day_ratio' => 70,
            ])
            ->assertOk()
            ->assertJsonPath('result.gross_kwp', 7.58)
            ->assertJsonPath('result.installed_kwp', 5.3)
            ->assertJsonPath('result.recommended_kwp', 5.3)
            ->assertJsonPath('result.price_per_kw', 7000000)
            ->assertJsonPath('result.investment_cost', 37100000)
            ->assertJsonCount(3, 'related_products')
            ->assertJsonPath('related_products.0.power_kw', 5.5)
            ->assertJsonPath('related_products.1.power_kw', 5.0)
            ->assertJsonPath('related_products.2.power_kw', 6.0);
    }

    public function test_news_api_returns_tag_color_in_argb_format(): void
    {
        $province = Province::query()->create([
            'code' => 'LA',
            'name' => 'Long An',
            'type' => 'Tỉnh/Thành',
            'is_active' => true,
        ]);

        $user = User::query()->create([
            'name' => 'News User',
            'phone' => '0911555666',
            'email' => 'news.user@example.com',
            'province_id' => $province->id,
            'password' => 'secret123',
        ]);

        $token = $user->createToken(config('api_auth.token_name', 'mobile-app'))->plainTextToken;

        $tag = Tag::query()->create([
            'name' => 'Tin tức nổi bật',
            'name_vi' => 'Tin tức nổi bật',
            'name_en' => 'Featured News',
            'slug' => 'tin-tuc-noi-bat',
            'color' => '#12ab34',
        ]);

        $post = Post::query()->create([
            'title' => 'Bai viet co mau tag',
            'slug' => 'bai-viet-co-mau-tag',
            'content' => 'Noi dung bai viet',
            'status' => 'published',
            'publish_at' => now()->subMinute(),
            'tag_id' => $tag->id,
        ]);

        $this->withHeaders($this->apiHeaders([
            'Authorization' => 'Bearer '.$token,
        ]))
            ->getJson('/api/news/tags')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.color', '0xFF12AB34')
            ->assertJsonPath('data.0.vi.slug', 'tin-tuc-noi-bat')
            ->assertJsonPath('data.0.en.name', 'Featured News');

        $this->withHeaders($this->apiHeaders([
            'Authorization' => 'Bearer '.$token,
        ]))
            ->getJson("/api/news/{$post->id}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.tag.color', '0xFF12AB34')
            ->assertJsonPath('data.tag.vi.name', 'Tin tức nổi bật');
    }
}
