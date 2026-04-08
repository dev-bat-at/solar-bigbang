<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\PostDetailResource;
use App\Http\Resources\Api\PostResource;
use App\Http\Resources\Api\TagResource;
use App\Models\Post;
use App\Models\Tag;
use App\Support\Api\ApiResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Tin tức', 'Quản lý tin tức, bài viết. Yêu cầu truyền Header X-API-KEY.', 40)]
class NewsController extends Controller
{
    #[Endpoint(
        operationId: 'getTags',
        title: 'Lấy danh sách thẻ (tags)',
        description: 'Trả về các từ khóa (tag) đang được sử dụng trong bài viết.'
    )]
    public function tags(Request $request): JsonResponse
    {
        $tags = Tag::query()
            ->whereHas('posts', function ($query) {
                $query->where('status', 'published')
                      ->where('publish_at', '<=', now());
            })
            ->orderBy('name')
            ->get();

        return ApiResponse::success(
            TagResource::collection($tags),
            'Lấy danh sách thẻ bài viết thành công.',
            'Tags retrieved successfully.'
        );
    }

    #[Endpoint(
        operationId: 'getNews',
        title: 'Lấy danh sách tin tức',
        description: 'Trả về danh sách tin tức (có phân trang). Cho phép filter theo tag (id hoặc slug) và tìm kiếm chuỗi qua tham số query.'
    )]
    public function index(Request $request): JsonResponse
    {
        $posts = Post::query()
            ->with(['tags'])
            ->where('status', 'published')
            ->where('publish_at', '<=', now())
            ->when($request->filled('tag'), function ($query) use ($request) {
                $query->whereHas('tags', function ($q) use ($request) {
                    $q->where('tags.slug', $request->input('tag'))
                      ->orWhere('tags.id', $request->input('tag'));
                });
            })
            ->when($request->filled('query'), function ($query) use ($request) {
                $search = $request->input('query');
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('content', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('publish_at')
            ->paginate($request->integer('per_page', 10));

        $resource = PostResource::collection($posts);
        
        // Add pagination meta to extra data so it matches format smoothly
        $extra = [
            'meta' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
            ]
        ];

        return ApiResponse::success(
            $resource,
            'Lấy danh sách tin tức thành công.',
            'News list retrieved successfully.',
            JsonResponse::HTTP_OK,
            $extra
        );
    }

    #[Endpoint(
        operationId: 'getNewsDetail',
        title: 'Lấy chi tiết tin tức',
        description: 'Xem chi tiết một bài viết qua ID hoặc Slug, bao gồm title_1, title_2, và thông tin Admin đăng bài.'
    )]
    public function show(Request $request, string $idOrSlug): JsonResponse
    {
        $post = Post::query()
            ->with(['tags', 'author'])
            ->where('status', 'published')
            ->where('publish_at', '<=', now())
            ->where(function ($q) use ($idOrSlug) {
                $q->where('slug', $idOrSlug)
                  ->orWhere('id', $idOrSlug);
            })
            ->first();

        if (! $post) {
            return ApiResponse::error(
                'Không tìm thấy bài viết hoặc bài viết chưa được xuất bản.',
                'Post not found or not published.',
                JsonResponse::HTTP_NOT_FOUND
            );
        }

        return ApiResponse::success(
            new PostDetailResource($post),
            'Lấy chi tiết tin tức thành công.',
            'News detail retrieved successfully.'
        );
    }
}
