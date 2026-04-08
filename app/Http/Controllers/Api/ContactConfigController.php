<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ContactConfigResource;
use App\Models\SystemSetting;
use App\Support\Api\ApiResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Cấu hình chung', 'Các API cấu hình công khai cho mobile/web.', 10)]
class ContactConfigController extends Controller
{
    #[Endpoint(
        operationId: 'getContactConfig',
        title: 'Lấy cấu hình liên hệ',
        description: 'Trả về số điện thoại, link Zalo, email và giờ làm việc của công ty.'
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $resource = new ContactConfigResource([
            'phone' => SystemSetting::get('contact_phone'),
            'zalo_link' => SystemSetting::get('contact_zalo_link'),
            'email' => SystemSetting::get('contact_email'),
            'business_hours' => SystemSetting::get('contact_business_hours'),
        ]);

        return ApiResponse::success(
            $resource,
            'Lấy cấu hình liên hệ thành công.',
            'Contact configuration retrieved successfully.'
        );
    }
}
