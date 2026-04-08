<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ProductCategoryResource;
use App\Http\Resources\Api\ProductResource;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Support\Api\ApiResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Sản phẩm', 'Quản lý danh sách sản phẩm và danh mục sản phẩm. Bắt buộc truyền Header Authorization và X-API-KEY.', 50)]
class ProductController extends Controller
{
    #[Endpoint(
        operationId: 'getProductCategories',
        title: 'Lấy danh mục sản phẩm',
        description: 'Trả về cấu trúc cây danh mục sản phẩm (bao gồm danh mục cha và các danh mục con trực thuộc).'
    )]
    public function categories(Request $request): JsonResponse
    {
        $categories = ProductCategory::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->with([
                'children' => function ($query) {
                    $query->where('is_active', true)->orderBy('sort_order')->orderBy('name');
                }
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return ApiResponse::success(
            ProductCategoryResource::collection($categories),
            'Lấy danh sách hệ danh mục sản phẩm thành công.',
            'Product categories retrieved successfully.'
        );
    }

    #[Endpoint(
        operationId: 'getProducts',
        title: 'Lấy danh sách sản phẩm',
        description: 'Lấy danh sách sản phẩm (có phân trang). Hỗ trợ tìm kiếm theo từ khóa (query), hoặc lọc theo category_id. Khi truyền category_id (ID danh mục cha hoặc con), hệ thống sẽ tự động quét và trả về đúng toàn bộ các sản phẩm liên quan.'
    )]
    public function index(Request $request): JsonResponse
    {
        $query = Product::query()
            ->with(['productCategory', 'productSubcategory'])
            ->where('status', 'published');

        if ($request->filled('query')) {
            $search = $request->input('query');
            $query->where(function ($q) use ($search) {
                $q->where('name_vi', 'like', "%{$search}%")
                    ->orWhere('name_en', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('tagline_vi', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $categoryId = $request->input('category_id');
            $query->where(function ($q) use ($categoryId) {
                $q->where('product_category_id', $categoryId)
                    ->orWhere('product_subcategory_id', $categoryId);
            });
        }

        $products = $query->orderByDesc('id')
            ->paginate($request->integer('per_page', 10));

        $resource = \App\Http\Resources\Api\ProductListResource::collection($products);

        $extra = [
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ]
        ];

        return ApiResponse::success(
            $resource,
            'Lấy danh sách sản phẩm thành công.',
            'Products list retrieved successfully.',
            JsonResponse::HTTP_OK,
            $extra
        );
    }

    #[Endpoint(
        operationId: 'getProductDetail',
        title: 'Lấy chi tiết sản phẩm',
        description: 'Lấy chi tiết sản phẩm với cấu trúc phân nhóm Đa ngôn ngữ (vi/en) đầy đủ các tab, thông số, tài liệu và FAQ.'
    )]
    public function show(Request $request, string $idOrSlug): JsonResponse
    {
        $product = Product::query()
            ->with(['productCategory', 'productSubcategory'])
            ->where('status', 'published')
            ->where(function ($q) use ($idOrSlug) {
                $q->where('slug', $idOrSlug)
                    ->orWhere('id', $idOrSlug);
            })
            ->first();

        if (!$product) {
            return ApiResponse::error(
                'Không tìm thấy sản phẩm.',
                'Product not found.',
                JsonResponse::HTTP_NOT_FOUND
            );
        }

        return ApiResponse::success(
            new ProductResource($product),
            'Lấy chi tiết sản phẩm thành công.',
            'Product details retrieved successfully.'
        );
    }
}
