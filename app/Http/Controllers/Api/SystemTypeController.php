<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\SystemTypeResource;
use App\Models\SystemType;
use App\Support\Api\ApiResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group('Danh mục Hệ thống', 'Quản lý thông tin biểu mẫu hệ thống.', 60)]
class SystemTypeController extends Controller
{
    #[Endpoint(
        operationId: 'getSystemTypes',
        title: 'Lấy danh sách hệ thống',
        description: 'Lấy danh sách các chuẩn hệ thống (Inverter, Battery...) đang có trong hệ thống.'
    )]
    public function index(): JsonResponse
    {
        $systems = SystemType::query()->orderBy('name')->get();

        return ApiResponse::success(
            SystemTypeResource::collection($systems),
            'Lấy danh mục hệ thống thành công.',
            'System types retrieved successfully.'
        );
    }
}
