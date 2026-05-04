<?php

namespace Tests\Feature\Api;

use App\Models\Dealer;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\LeadTimeline;
use App\Models\Post;
use App\Models\Product;
use App\Models\Province;
use App\Models\SystemSetting;
use App\Models\SystemType;
use App\Models\SupportRequest;
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

    public function test_can_calculate_hybrid_quote_with_bill_multiplier_formula(): void
    {
        $province = Province::query()->create([
            'code' => 'BD',
            'name' => 'Binh Duong',
            'type' => 'Tỉnh/Thành',
            'is_active' => true,
        ]);

        $user = User::query()->create([
            'name' => 'Hybrid Quote User',
            'phone' => '0911444000',
            'email' => 'hybrid.quote.user@example.com',
            'province_id' => $province->id,
            'password' => 'secret123',
        ]);

        $token = $user->createToken(config('api_auth.token_name', 'mobile-app'))->plainTextToken;

        $systemType = SystemType::query()->create([
            'name' => 'Hệ Hybrid',
            'name_vi' => 'Hệ Hybrid',
            'name_en' => 'Hybrid System',
            'slug' => 'hybrid-bill-multiplier',
            'description' => 'Hệ Hybrid',
            'description_vi' => 'Hệ Hybrid',
            'description_en' => 'Hybrid System',
            'quote_formula_type' => 'hybrid',
            'quote_is_active' => true,
            'quote_settings' => [
                'electric_price' => 2500,
                'yield' => 120,
                'market_factor' => 1,
                'three_phase_price_factor' => 1.1,
                'three_phase_kw_factor' => 0.91,
                'bill_multiplier_tiers' => [
                    ['min_bill' => 1500000, 'max_bill' => 3000000, 'multiplier' => 52],
                    ['min_bill' => 3500000, 'max_bill' => 5000000, 'multiplier' => 48],
                ],
            ],
        ]);

        $this->withHeaders($this->apiHeaders([
            'Authorization' => 'Bearer '.$token,
        ]))
            ->postJson('/api/quote/calculate', [
                'system_type_id' => $systemType->id,
                'phase_type' => '1P',
                'monthly_bill' => 3000000,
            ])
            ->assertOk()
            ->assertJsonPath('result.bill_multiplier', 52.0)
            ->assertJsonPath('result.investment_cost', 156000000)
            ->assertJsonPath('result.recommended_kwp', 10.0);
    }

    public function test_can_list_systems_with_unified_quote_metadata(): void
    {
        $province = Province::query()->create([
            'code' => 'VT',
            'name' => 'Vung Tau',
            'type' => 'Tỉnh/Thành',
            'is_active' => true,
        ]);

        $user = User::query()->create([
            'name' => 'Systems User',
            'phone' => '0911666777',
            'email' => 'systems.user@example.com',
            'province_id' => $province->id,
            'password' => 'secret123',
        ]);

        $token = $user->createToken(config('api_auth.token_name', 'mobile-app'))->plainTextToken;

        SystemType::query()->create([
            'name' => 'Hybrid',
            'name_vi' => 'Hệ Hybrid',
            'name_en' => 'Hybrid System',
            'slug' => 'he-hybrid',
            'description' => 'Hệ Hybrid',
            'description_vi' => 'Hệ Hybrid',
            'description_en' => 'Hybrid System',
            'quote_formula_type' => 'hybrid',
            'quote_is_active' => true,
            'show_calculation_formula' => true,
            'quote_request_fields' => [
                [
                    'key' => 'roof_area',
                    'label_vi' => 'Diện tích mái',
                    'label_en' => 'Roof area',
                    'input_type' => 'number',
                    'required' => true,
                ],
            ],
            'quote_settings' => [
                'day_ratio_default' => 70,
            ],
        ]);

        $this->withHeaders($this->apiHeaders([
            'Authorization' => 'Bearer '.$token,
        ]))
            ->getJson('/api/systems')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.slug', 'he-hybrid')
            ->assertJsonPath('data.0.quote_enabled', true)
            ->assertJsonPath('data.0.show_calculation_formula', true)
            ->assertJsonPath('data.0.input_mode', 'custom_fields')
            ->assertJsonPath('data.0.quote_fields.0.key', 'roof_area')
            ->assertJsonPath('data.0.name_vi', 'Hệ Hybrid')
            ->assertJsonPath('data.0.name_en', 'Hybrid System');
    }

    public function test_can_list_systems_with_admin_configured_ratio_fields(): void
    {
        $province = Province::query()->create([
            'code' => 'NT',
            'name' => 'Ninh Thuan',
            'type' => 'Tỉnh/Thành',
            'is_active' => true,
        ]);

        $user = User::query()->create([
            'name' => 'Ratio Fields User',
            'phone' => '0911666000',
            'email' => 'ratio.fields.user@example.com',
            'province_id' => $province->id,
            'password' => 'secret123',
        ]);

        $token = $user->createToken(config('api_auth.token_name', 'mobile-app'))->plainTextToken;

        SystemType::query()->create([
            'name' => 'On-grid',
            'name_vi' => 'Hệ hòa lưới',
            'name_en' => 'On-grid System',
            'slug' => 'he-hoa-luoi-ratio',
            'description' => 'Hệ hòa lưới',
            'description_vi' => 'Hệ hòa lưới',
            'description_en' => 'On-grid System',
            'quote_formula_type' => 'bam_tai',
            'quote_is_active' => true,
            'show_calculation_formula' => false,
            'quote_settings' => [
                'day_ratio_default' => 65,
                'ratio_fields' => [
                    [
                        'key' => 'start_day',
                        'label_vi' => 'Tỷ lệ ngày bắt đầu',
                        'label_en' => 'Start day ratio',
                        'placeholder_vi' => 'Nhập tỷ lệ ngày',
                        'placeholder_en' => 'Enter day ratio',
                    ],
                    [
                        'key' => 'end_night',
                        'label_vi' => 'Tỷ lệ đêm kết thúc',
                        'label_en' => 'End night ratio',
                        'placeholder_vi' => 'Nhập tỷ lệ đêm',
                        'placeholder_en' => 'Enter night ratio',
                    ],
                ],
            ],
        ]);

        $this->withHeaders($this->apiHeaders([
            'Authorization' => 'Bearer '.$token,
        ]))
            ->getJson('/api/systems')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.input_mode', 'day_night_ratio')
            ->assertJsonPath('data.0.quote_fields.0.key', 'start_day')
            ->assertJsonPath('data.0.quote_fields.0.label_vi', 'Tỷ lệ ngày bắt đầu')
            ->assertJsonPath('data.0.quote_fields.0.default_value', 65.0)
            ->assertJsonPath('data.0.quote_fields.1.key', 'end_night');
    }

    public function test_can_submit_general_support_request_via_original_endpoint(): void
    {
        $province = Province::query()->create([
            'code' => 'DL',
            'name' => 'Dak Lak',
            'type' => 'Tỉnh/Thành',
            'is_active' => true,
        ]);

        $user = User::query()->create([
            'name' => 'Support User',
            'phone' => '0911888999',
            'email' => 'support.user@example.com',
            'province_id' => $province->id,
            'password' => 'secret123',
        ]);

        $token = $user->createToken(config('api_auth.token_name', 'mobile-app'))->plainTextToken;

        $this->withHeaders($this->apiHeaders([
            'Authorization' => 'Bearer '.$token,
        ]))
            ->postJson('/api/support-requests', [
                'name' => 'Nguyen Van A',
                'phone' => '0909123456',
                'type' => 'general_contact',
                'message' => 'Can duoc tu van tong quan',
            ])
            ->assertCreated()
            ->assertJsonPath('success', true);

        $supportRequest = SupportRequest::query()->firstOrFail();

        $this->assertSame('general_contact', $supportRequest->request_type);
        $this->assertNull($supportRequest->request_payload);
        $this->assertSame('Can duoc tu van tong quan', $supportRequest->customer_message);
    }

    public function test_can_submit_dealer_support_request_and_persist_notes_to_customer_and_timeline(): void
    {
        $province = Province::query()->create([
            'code' => 'BD',
            'name' => 'Binh Duong',
            'type' => 'Tỉnh/Thành',
            'is_active' => true,
        ]);

        $user = User::query()->create([
            'name' => 'Dealer Support User',
            'phone' => '0911222000',
            'email' => 'dealer.support.user@example.com',
            'province_id' => $province->id,
            'password' => 'secret123',
        ]);

        $dealer = Dealer::query()->create([
            'name' => 'Dealer Support',
            'code' => 'DLR-SUPPORT-001',
            'phone' => '0909000111',
            'email' => 'dealer.support@example.com',
            'password' => 'secret123',
            'status' => 'approved',
        ]);

        $systemType = SystemType::query()->create([
            'name' => 'Hybrid',
            'name_vi' => 'Hệ Hybrid',
            'name_en' => 'Hybrid System',
            'slug' => 'hybrid-dealer-support',
            'description' => 'Hệ Hybrid',
            'description_vi' => 'Hệ Hybrid',
            'description_en' => 'Hybrid System',
        ]);

        $token = $user->createToken(config('api_auth.token_name', 'mobile-app'))->plainTextToken;

        $this->withHeaders($this->apiHeaders([
            'Authorization' => 'Bearer '.$token,
        ]))
            ->postJson("/api/dealers/{$dealer->id}/support-requests", [
                'name' => 'Nguyen Van C',
                'phone' => '0909888777',
                'email' => 'nguyenvanc@example.com',
                'address' => 'Thu Dau Mot, Binh Duong',
                'system_type_id' => $systemType->id,
                'contact_time' => 'Buoi chieu',
                'notes' => 'Can tu van ky ve cong suat va chi phi.',
            ])
            ->assertCreated()
            ->assertJsonPath('success', true);

        $customer = Customer::query()->firstOrFail();
        $lead = Lead::query()->firstOrFail();
        $timeline = LeadTimeline::query()->firstOrFail();

        $this->assertSame($dealer->id, $customer->dealer_id);
        $this->assertSame('Can tu van ky ve cong suat va chi phi.', $customer->notes);
        $this->assertSame($customer->id, $lead->customer_id);
        $this->assertSame('Can tu van ky ve cong suat va chi phi.', data_get($timeline->payload, 'notes'));
    }

    public function test_system_quote_request_endpoint_stores_custom_fields_when_formula_display_is_enabled(): void
    {
        $systemType = SystemType::query()->create([
            'name' => 'Hybrid',
            'name_vi' => 'Hệ Hybrid',
            'name_en' => 'Hybrid System',
            'slug' => 'hybrid-custom-fields',
            'description' => 'Hệ Hybrid',
            'description_vi' => 'Hệ Hybrid',
            'description_en' => 'Hybrid System',
            'quote_formula_type' => 'hybrid',
            'quote_is_active' => true,
            'show_calculation_formula' => true,
            'quote_request_fields' => [
                [
                    'key' => 'roof_area',
                    'label_vi' => 'Diện tích mái',
                    'label_en' => 'Roof area',
                    'input_type' => 'number',
                    'required' => true,
                ],
                [
                    'key' => 'usage_note',
                    'label_vi' => 'Mô tả nhu cầu',
                    'label_en' => 'Usage note',
                    'input_type' => 'textarea',
                    'required' => false,
                ],
            ],
        ]);

        $dealerA = Dealer::query()->create([
            'name' => 'Dealer Quote A',
            'code' => 'DLR-QUOTE-A',
            'phone' => '0909555001',
            'email' => 'dealer.quote.a@example.com',
            'password' => 'secret123',
            'status' => 'approved',
        ]);

        $dealerB = Dealer::query()->create([
            'name' => 'Dealer Quote B',
            'code' => 'DLR-QUOTE-B',
            'phone' => '0909555002',
            'email' => 'dealer.quote.b@example.com',
            'password' => 'secret123',
            'status' => 'approved',
        ]);

        $province = Province::query()->create([
            'code' => 'DL',
            'name' => 'Dak Lak',
            'type' => 'Tỉnh/Thành',
            'is_active' => true,
        ]);

        $user = User::query()->create([
            'name' => 'Support User',
            'phone' => '0911888999',
            'email' => 'support.user@example.com',
            'province_id' => $province->id,
            'password' => 'secret123',
        ]);

        $token = $user->createToken(config('api_auth.token_name', 'mobile-app'))->plainTextToken;

        $this->withHeaders($this->apiHeaders([
            'Authorization' => 'Bearer '.$token,
        ]))
            ->postJson('/api/systems/quote-requests', [
                'name' => 'Nguyen Van A',
                'phone' => '0909123456',
                'email' => 'nguyenvana@example.com',
                'system_type_id' => $systemType->id,
                'dealer_ids' => [$dealerA->id, $dealerB->id],
                'request_payload' => [
                    'roof_area' => '120',
                    'usage_note' => 'Can luu tru cho buoi toi',
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.dealer_request_count', 2);

        $customers = Customer::query()->orderBy('dealer_id')->get();
        $timelines = LeadTimeline::query()->orderBy('id')->get();

        $this->assertCount(2, $customers);
        $this->assertCount(2, Lead::query()->get());
        $this->assertCount(2, $timelines);
        $this->assertDatabaseCount('support_requests', 0);
        $this->assertSame($dealerA->id, $customers[0]->dealer_id);
        $this->assertSame($dealerB->id, $customers[1]->dealer_id);
        $this->assertSame('Bất cứ lúc nào', $customers[0]->contact_time);
        $this->assertStringContainsString('Biểu mẫu: Công thức tính', (string) $customers[0]->notes);
        $this->assertStringContainsString('Diện tích mái: 120', (string) $customers[0]->notes);
        $this->assertSame('Bất cứ lúc nào', data_get($timelines[0]->payload, 'contact_time'));
        $this->assertSame('system_quote_custom', data_get($timelines[0]->payload, 'request_payload.mode'));
        $this->assertSame('nguyenvana@example.com', data_get($timelines[0]->payload, 'email'));
        $this->assertSame('roof_area', data_get($timelines[0]->payload, 'request_payload.fields.0.key'));
        $this->assertSame('120', data_get($timelines[0]->payload, 'request_payload.fields.0.value'));
        $this->assertSame('usage_note', data_get($timelines[0]->payload, 'request_payload.fields.1.key'));
    }

    public function test_system_quote_request_endpoint_stores_standard_system_fields_when_formula_display_is_disabled(): void
    {
        $province = Province::query()->create([
            'code' => 'KH',
            'name' => 'Khanh Hoa',
            'type' => 'Tỉnh/Thành',
            'is_active' => true,
        ]);

        $user = User::query()->create([
            'name' => 'Ratio User',
            'phone' => '0911999000',
            'email' => 'ratio.user@example.com',
            'province_id' => $province->id,
            'password' => 'secret123',
        ]);

        $token = $user->createToken(config('api_auth.token_name', 'mobile-app'))->plainTextToken;

        $systemType = SystemType::query()->create([
            'name' => 'On-grid',
            'name_vi' => 'Hệ hòa lưới',
            'name_en' => 'On-grid System',
            'slug' => 'on-grid-ratio-fields',
            'description' => 'Hệ hòa lưới',
            'description_vi' => 'Hệ hòa lưới',
            'description_en' => 'On-grid System',
            'quote_formula_type' => 'bam_tai',
            'quote_is_active' => true,
            'show_calculation_formula' => false,
            'quote_settings' => [
                'day_ratio_default' => 70,
            ],
        ]);

        $dealer = Dealer::query()->create([
            'name' => 'Dealer Ratio',
            'code' => 'DLR-RATIO-001',
            'phone' => '0909777001',
            'email' => 'dealer.ratio@example.com',
            'password' => 'secret123',
            'status' => 'approved',
        ]);

        $this->withHeaders($this->apiHeaders([
            'Authorization' => 'Bearer '.$token,
        ]))
            ->postJson('/api/systems/quote-requests', [
                'name' => 'Tran Thi B',
                'phone' => '0909234567',
                'system_type_id' => $systemType->id,
                'dealer_ids' => [$dealer->id],
                'monthly_bill' => 1800000,
                'phase_type' => '1P',
                'start_day' => 65,
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.dealer_request_count', 1);

        $customer = Customer::query()->latest('id')->firstOrFail();
        $timeline = LeadTimeline::query()->latest('id')->firstOrFail();

        $this->assertSame($dealer->id, $customer->dealer_id);
        $this->assertSame('Bất cứ lúc nào', $customer->contact_time);
        $this->assertStringContainsString('Tiền điện trung bình tháng: 1.800.000 VNĐ', (string) $customer->notes);
        $this->assertSame('Bất cứ lúc nào', data_get($timeline->payload, 'contact_time'));
        $this->assertSame('system_quote_standard', data_get($timeline->payload, 'request_payload.mode'));
        $this->assertSame(1800000.0, data_get($timeline->payload, 'request_payload.fields.0.value'));
        $this->assertSame('1P', data_get($timeline->payload, 'request_payload.fields.1.value'));
        $this->assertSame(65.0, data_get($timeline->payload, 'request_payload.fields.2.value'));
        $this->assertSame(35.0, data_get($timeline->payload, 'request_payload.fields.3.value'));
        $this->assertDatabaseCount('support_requests', 0);
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
