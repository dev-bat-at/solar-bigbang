<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\SystemTypeResource;
use App\Models\SystemType;
use App\Support\Api\ApiResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Danh mục Hệ thống', 'Quản lý thông tin biểu mẫu hệ thống.', 60)]
class SystemTypeController extends Controller
{
    #[Endpoint(
        operationId: 'getSystemTypes',
        title: 'Lấy danh sách hệ thống',
        description: 'Lấy danh sách các hệ thống. Có thể truyền query param quote_enabled=true để chỉ lấy hệ đang bật báo giá, hoặc quote_enabled=all để lấy tất cả.'
    )]
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'quote_enabled' => ['nullable', 'string', 'in:true,false,1,0,all'],
        ]);

        $quoteEnabledFilter = strtolower((string) ($validated['quote_enabled'] ?? 'all'));

        $systems = SystemType::query()
            ->when($quoteEnabledFilter !== 'all', function ($query) use ($quoteEnabledFilter) {
                $query->where('quote_is_active', in_array($quoteEnabledFilter, ['true', '1'], true));
            })
            ->orderBy('name_vi')
            ->orderBy('name')
            ->get();

        return ApiResponse::success(
            SystemTypeResource::collection($systems),
            'Lấy danh mục hệ thống thành công.',
            'System types retrieved successfully.'
        );
    }
}
