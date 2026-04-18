<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreDealerProjectRequest;
use App\Http\Resources\Api\DealerProjectResource;
use App\Models\Dealer;
use App\Models\Project;
use App\Support\Api\ApiResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Group('Công trình đại lý', 'Danh sách và tạo mới công trình cho tài khoản đại lý đang đăng nhập.', 71)]
class DealerProjectController extends Controller
{
    #[Endpoint(
        operationId: 'getDealerProjects',
        title: 'Lấy danh sách công trình của đại lý',
        description: 'Lấy danh sách phân trang các công trình thuộc tài khoản đại lý đang đăng nhập.'
    )]
    public function index(Request $request): JsonResponse
    {
        $dealer = $this->resolveAuthenticatedDealer($request);

        if (! $dealer instanceof Dealer) {
            return ApiResponse::error(
                'Chỉ tài khoản đại lý mới có quyền xem danh sách công trình.',
                'Only dealer accounts can view dealer projects.',
                JsonResponse::HTTP_FORBIDDEN
            );
        }

        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $projects = $dealer->projects()
            ->with(['systemType', 'province'])
            ->orderByDesc('created_at')
            ->paginate((int) ($validated['per_page'] ?? 10));

        return ApiResponse::success(
            DealerProjectResource::collection($projects),
            'Lấy danh sách công trình thành công.',
            'Dealer projects retrieved successfully.',
            JsonResponse::HTTP_OK,
            [
                'meta' => [
                    'current_page' => $projects->currentPage(),
                    'last_page' => $projects->lastPage(),
                    'per_page' => $projects->perPage(),
                    'total' => $projects->total(),
                ],
            ]
        );
    }

    #[Endpoint(
        operationId: 'storeDealerProject',
        title: 'Tạo mới công trình cho đại lý',
        description: 'Tạo công trình mới cho tài khoản đại lý đang đăng nhập. Ảnh được lưu trong thư mục public/projects và công trình mặc định ở trạng thái chờ duyệt.'
    )]
    public function store(StoreDealerProjectRequest $request): JsonResponse
    {
        $dealer = $this->resolveAuthenticatedDealer($request);

        if (! $dealer instanceof Dealer) {
            return ApiResponse::error(
                'Chỉ tài khoản đại lý mới có quyền tạo công trình.',
                'Only dealer accounts can create dealer projects.',
                JsonResponse::HTTP_FORBIDDEN
            );
        }

        $validated = $request->validated();
        $storedImages = [];

        try {
            foreach ($request->file('images', []) as $image) {
                $storedImages[] = $image->store('projects', 'root_public');
            }

            $project = DB::transaction(function () use ($dealer, $validated, $storedImages, $request) {
                $project = Project::query()->create([
                    'dealer_id' => $dealer->id,
                    'title' => $validated['title'],
                    'system_type_id' => $validated['system_type_id'],
                    'province_id' => $validated['province_id'],
                    'price' => $validated['price'],
                    'address' => $validated['address'],
                    'images' => $storedImages,
                    'description' => $validated['description'] ?? null,
                    'capacity' => $validated['capacity'],
                    'completion_date' => $validated['completion_date'],
                    'status' => Project::STATUS_PENDING,
                ]);

                activity('api')
                    ->causedBy($dealer)
                    ->performedOn($project)
                    ->event('dealer_project_created')
                    ->withProperties([
                        'channel' => 'api',
                        'guard' => 'sanctum-dealer',
                        'ip' => $request->ip(),
                        'user_agent' => Str::limit((string) $request->userAgent(), 255),
                        'dealer_id' => $dealer->id,
                        'dealer_name' => $dealer->name,
                        'project_title' => $project->title,
                        'system_type_id' => $project->system_type_id,
                        'province_id' => $project->province_id,
                        'price' => $project->price,
                        'image_count' => count($storedImages),
                        'status' => $project->status,
                    ])
                    ->log('dealer_project_created');

                return $project;
            });
        } catch (\Throwable $exception) {
            if ($storedImages !== []) {
                Storage::disk('root_public')->delete($storedImages);
            }

            report($exception);

            return ApiResponse::error(
                'Không thể tạo công trình vào lúc này. Vui lòng thử lại sau.',
                'Unable to create the project right now. Please try again later.',
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        $project->load(['systemType', 'province']);

        return ApiResponse::success(
            new DealerProjectResource($project),
            'Tạo công trình thành công. Công trình đang chờ duyệt.',
            'Project created successfully and is pending approval.',
            JsonResponse::HTTP_CREATED
        );
    }

    protected function resolveAuthenticatedDealer(Request $request): ?Dealer
    {
        $user = $request->user();

        return $user instanceof Dealer ? $user : null;
    }
}
