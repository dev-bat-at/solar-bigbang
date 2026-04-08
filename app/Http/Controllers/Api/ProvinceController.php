<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ProvinceResource;
use App\Models\Province;
use App\Support\Api\ApiResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Tỉnh thành', 'Danh sách tỉnh/thành phục vụ đăng ký người dùng.', 20)]
class ProvinceController extends Controller
{
    #[Endpoint(
        operationId: 'listProvinces',
        title: 'Lấy danh sách tỉnh thành',
        description: 'Trả về danh sách tỉnh/thành đang hoạt động để client hiển thị cho người dùng chọn.'
    )]
    public function index(Request $request): JsonResponse
    {
        $provinces = Province::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->withCount('children')
            ->orderBy('name')
            ->get();

        return ApiResponse::success(
            ProvinceResource::collection($provinces),
            'Lấy danh sách tỉnh/thành thành công.',
            'Provinces retrieved successfully.'
        );
    }

    #[Endpoint(
        operationId: 'getProvince',
        title: 'Lấy chi tiết tỉnh thành',
        description: 'Trả về thông tin chi tiết của một tỉnh/thành theo ID.'
    )]
    public function show(Request $request, Province $province): JsonResponse
    {
        if ($province->parent_id !== null) {
            return ApiResponse::error(
                'Không tìm thấy tỉnh/thành.',
                'Province not found.',
                JsonResponse::HTTP_NOT_FOUND
            );
        }

        $province->loadCount('children');

        return ApiResponse::success(
            new ProvinceResource($province),
            'Lấy chi tiết tỉnh/thành thành công.',
            'Province details retrieved successfully.'
        );
    }
}
