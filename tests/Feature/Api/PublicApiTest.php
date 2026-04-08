<?php

namespace Tests\Feature\Api;

use App\Models\Province;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
