<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\DealerNotificationResource;
use App\Models\Dealer;
use App\Models\DealerNotification;
use App\Support\Api\ApiResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

#[Group('Đại lý', 'Thông báo dành cho tài khoản đại lý.', 71)]
class DealerNotificationController extends Controller
{
    #[Endpoint(
        operationId: 'getDealerNotifications',
        title: 'Lấy danh sách thông báo đại lý',
        description: 'Lấy danh sách thông báo của tài khoản đại lý đang đăng nhập. Hỗ trợ lọc theo trạng thái unread/read.'
    )]
    public function index(Request $request): JsonResponse
    {
        $dealer = $this->resolveAuthenticatedDealer($request);

        if (! $dealer instanceof Dealer) {
            return ApiResponse::error(
                'Chỉ tài khoản đại lý mới có quyền xem thông báo.',
                'Only dealer accounts can view notifications.',
                JsonResponse::HTTP_FORBIDDEN
            );
        }

        $validated = $request->validate([
            'status' => ['nullable', 'string', Rule::in(['all', ...array_keys(DealerNotification::statusOptions())])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $notifications = $dealer->notifications()
            ->when(
                filled($validated['status'] ?? null) && ($validated['status'] !== 'all'),
                fn ($query) => $query->where('status', $validated['status'])
            )
            ->orderByDesc('id')
            ->paginate((int) ($validated['per_page'] ?? 10));

        $unreadCount = $dealer->notifications()
            ->where('status', DealerNotification::STATUS_UNREAD)
            ->count();

        return ApiResponse::success(
            DealerNotificationResource::collection($notifications),
            'Lấy danh sách thông báo thành công.',
            'Dealer notifications retrieved successfully.',
            JsonResponse::HTTP_OK,
            [
                'meta' => [
                    'current_page' => $notifications->currentPage(),
                    'last_page' => $notifications->lastPage(),
                    'per_page' => $notifications->perPage(),
                    'total' => $notifications->total(),
                    'unread_count' => $unreadCount,
                ],
            ]
        );
    }

    #[Endpoint(
        operationId: 'updateDealerNotificationStatus',
        title: 'Cập nhật trạng thái thông báo đại lý',
        description: 'Cho phép đại lý cập nhật trạng thái một thông báo sang đã đọc hoặc chưa đọc.'
    )]
    public function updateStatus(Request $request, DealerNotification $notification): JsonResponse
    {
        $dealer = $this->resolveAuthenticatedDealer($request);

        if (! $dealer instanceof Dealer) {
            return ApiResponse::error(
                'Chỉ tài khoản đại lý mới có quyền cập nhật trạng thái thông báo.',
                'Only dealer accounts can update notification status.',
                JsonResponse::HTTP_FORBIDDEN
            );
        }

        if ((int) $notification->dealer_id !== (int) $dealer->id) {
            return ApiResponse::error(
                'Thông báo không thuộc đại lý hiện tại.',
                'Notification does not belong to the authenticated dealer.',
                JsonResponse::HTTP_FORBIDDEN
            );
        }

        $validated = $request->validate([
            'status' => ['nullable', 'string', Rule::in(array_keys(DealerNotification::statusOptions()))],
        ]);

        $status = $validated['status'] ?? DealerNotification::STATUS_READ;

        $notification->update([
            'status' => $status,
            'read_at' => $status === DealerNotification::STATUS_READ ? now() : null,
        ]);

        return ApiResponse::success(
            new DealerNotificationResource($notification->fresh()),
            'Cập nhật trạng thái thông báo thành công.',
            'Dealer notification status updated successfully.'
        );
    }

    protected function resolveAuthenticatedDealer(Request $request): ?Dealer
    {
        $user = $request->user();

        return $user instanceof Dealer ? $user : null;
    }
}
