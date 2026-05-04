<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\DealerCustomerResource;
use App\Http\Resources\Api\DealerDetailResource;
use App\Http\Resources\Api\DealerAuthResource;
use App\Http\Resources\Api\DealerListResource;
use App\Models\Customer;
use App\Models\Dealer;
use App\Models\SupportRequest;
use App\Services\DealerNotificationService;
use App\Support\Api\ApiResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

#[Group('Đại lý', 'Quản lý thông tin Đại lý (Dealers) và hỗ trợ gửi yêu cầu support trực tiếp.', 70)]
class DealerController extends Controller
{
    #[Endpoint(
        operationId: 'getDealerCustomers',
        title: 'Lấy danh sách khách hàng đại lý',
        description: 'Lấy danh sách khách hàng thuộc tài khoản đại lý đang đăng nhập. Hỗ trợ tìm kiếm theo query và lọc theo trạng thái. Nếu không truyền status hoặc truyền all thì lấy tất cả.'
    )]
    public function customers(Request $request): JsonResponse
    {
        $dealer = $this->resolveAuthenticatedDealer($request);

        if (! $dealer instanceof Dealer) {
            return ApiResponse::error(
                'Chỉ tài khoản đại lý mới có quyền xem danh sách khách hàng.',
                'Only dealer accounts can view dealer customers.',
                JsonResponse::HTTP_FORBIDDEN
            );
        }

        $validated = $request->validate([
            'query' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', Rule::in(['all', ...array_keys(Customer::statusOptions())])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $customers = $dealer->customers()
            ->with('systemType')
            ->when(filled($validated['query'] ?? null), function ($query) use ($validated) {
                $search = trim((string) $validated['query']);

                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                });
            })
            ->when(
                filled($validated['status'] ?? null) && ($validated['status'] !== 'all'),
                fn ($query) => $query->where('status', $validated['status'])
            )
            ->orderByDesc('id')
            ->paginate((int) ($validated['per_page'] ?? 10));

        return ApiResponse::success(
            DealerCustomerResource::collection($customers),
            'Lấy danh sách khách hàng đại lý thành công.',
            'Dealer customers retrieved successfully.',
            JsonResponse::HTTP_OK,
            [
                'meta' => [
                    'current_page' => $customers->currentPage(),
                    'last_page' => $customers->lastPage(),
                    'per_page' => $customers->perPage(),
                    'total' => $customers->total(),
                ],
            ]
        );
    }

    #[Endpoint(
        operationId: 'updateAuthenticatedDealerAvatar',
        title: 'Cập nhật ảnh đại lý',
        description: 'Cho phép đại lý đang đăng nhập cập nhật duy nhất ảnh đại diện của mình bằng file upload multipart/form-data.'
    )]
    public function updateAvatar(Request $request): JsonResponse
    {
        $dealer = $this->resolveAuthenticatedDealer($request);

        if (! $dealer instanceof Dealer) {
            return ApiResponse::error(
                'Chỉ tài khoản đại lý mới có quyền cập nhật ảnh đại diện.',
                'Only dealer accounts can update dealer avatar.',
                JsonResponse::HTTP_FORBIDDEN
            );
        }

        $validated = $request->validate([
            'avatar' => ['required', 'image', 'max:5120'],
        ]);

        $path = $validated['avatar']->store('dealers/avatars', 'root_public');

        $dealer->forceFill([
            'avatar' => $path,
        ])->save();

        activity('api')
            ->causedBy($dealer)
            ->performedOn($dealer)
            ->event('dealer_avatar_updated')
            ->withProperties([
                'channel' => 'api',
                'guard' => 'sanctum-dealer',
                'ip' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 255),
                'avatar' => $path,
            ])
            ->log('dealer_avatar_updated');

        return ApiResponse::success(
            new DealerAuthResource($dealer->fresh()),
            'Cập nhật ảnh đại lý thành công.',
            'Dealer avatar updated successfully.'
        );
    }

    #[Endpoint(
        operationId: 'updateDealerCustomerStatus',
        title: 'Cập nhật trạng thái khách hàng đại lý',
        description: 'Cho phép đại lý cập nhật trạng thái xử lý của khách hàng thuộc chính đại lý đó.'
    )]
    public function updateCustomerStatus(Request $request, Customer $customer): JsonResponse
    {
        $dealer = $this->resolveAuthenticatedDealer($request);

        if (! $dealer instanceof Dealer) {
            return ApiResponse::error(
                'Chỉ tài khoản đại lý mới có quyền cập nhật trạng thái khách hàng.',
                'Only dealer accounts can update dealer customer status.',
                JsonResponse::HTTP_FORBIDDEN
            );
        }

        if ((int) $customer->dealer_id !== (int) $dealer->id) {
            return ApiResponse::error(
                'Khách hàng không thuộc đại lý hiện tại.',
                'Customer does not belong to the authenticated dealer.',
                JsonResponse::HTTP_FORBIDDEN
            );
        }

        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(array_keys(Customer::statusOptions()))],
        ]);

        $oldStatus = $customer->status;

        $customer->update([
            'status' => $validated['status'],
        ]);

        $customer->load('systemType');

        activity('api')
            ->causedBy($dealer)
            ->performedOn($customer)
            ->event('customer_status_updated')
            ->withProperties([
                'channel' => 'api',
                'guard' => 'sanctum-dealer',
                'ip' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 255),
                'dealer_id' => $dealer->id,
                'dealer_name' => $dealer->name,
                'old_status' => $oldStatus,
                'new_status' => $customer->status,
            ])
            ->log('customer_status_updated');

        return ApiResponse::success(
            new DealerCustomerResource($customer),
            'Cập nhật trạng thái khách hàng thành công.',
            'Dealer customer status updated successfully.'
        );
    }

    #[Endpoint(
        operationId: 'getDealers',
        title: 'Lấy danh sách đại lý',
        description: 'Trả danh sách phân trang các đại lý đang hoạt động (approved/active). Hỗ trợ lọc theo query (tên, mã) và lọc theo tỉnh/thành (province_id).'
    )]
    public function index(Request $request): JsonResponse
    {
        $query = Dealer::query()
            ->whereIn('status', ['approved', 'active']); // Rule 8.2: Đại lý approved/active mới public

        if ($request->filled('query')) {
            $search = $request->input('query');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('province_id')) {
            $provinceId = $request->input('province_id');
            $query->where(function ($q) use ($provinceId) {
                $q->where('province_id', $provinceId)
                  ->orWhereJsonContains('coverage_area', (string)$provinceId)
                  ->orWhereJsonContains('coverage_area', (int)$provinceId);
            });
        }

        // Ưu tiên priority order (theo Rule 8.2) và random/latest
        $dealers = $query->orderByDesc('priority_order')
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 10));

        return ApiResponse::success(
            DealerListResource::collection($dealers),
            'Lấy danh sách đại lý thành công.',
            'Dealers list retrieved successfully.',
            JsonResponse::HTTP_OK,
            [
                'meta' => [
                    'current_page' => $dealers->currentPage(),
                    'last_page' => $dealers->lastPage(),
                    'per_page' => $dealers->perPage(),
                    'total' => $dealers->total(),
                ]
            ]
        );
    }

    #[Endpoint(
        operationId: 'getDealerDetail',
        title: 'Lấy chi tiết đại lý',
        description: 'Lấy thông tin chi tiết đại lý bao gồm luôn mảng các Dự án/Công trình của đại lý đang/đã thi công.'
    )]
    public function show($id): JsonResponse
    {
        $dealer = Dealer::query()
            ->whereIn('status', ['approved', 'active'])
            ->with(['projects' => function ($q) {
                // Lấy công trình public / không bị xoá
                $q->with('systemType')->orderByDesc('id');
            }])
            ->find($id);

        if (! $dealer) {
            return ApiResponse::error(
                'Không tìm thấy đại lý hoặc đại lý không hợp lệ.',
                'Dealer not found or inactive.',
                JsonResponse::HTTP_NOT_FOUND
            );
        }

        return ApiResponse::success(
            new DealerDetailResource($dealer),
            'Lấy chi tiết đại lý thành công.',
            'Dealer details retrieved successfully.'
        );
    }

    #[Endpoint(
        operationId: 'requestDealerSupport',
        title: 'Gửi yêu cầu hỗ trợ đến đại lý',
        description: 'Tạo khách hàng mới (Customer) cho Đại lý và khởi tạo một Lead kèm thông tin yêu cầu tư vấn (hệ thống, thời gian liên hệ, ghi chú).'
    )]
    public function requestSupport(Request $request, $id): JsonResponse
    {
        $dealer = Dealer::query()->whereIn('status', ['approved', 'active'])->find($id);

        if (! $dealer) {
            return ApiResponse::error('Đại lý không tồn tại.', 'Dealer not found.', JsonResponse::HTTP_NOT_FOUND);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'required|string|max:1000',
            'system_type_id' => 'required|exists:system_types,id',
            'contact_time' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
        ], [
            'required' => 'Trường :attribute là bắt buộc.',
            'exists' => ':attribute không hợp lệ trong hệ thống.',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error(
                $validator->errors()->first(),
                'Validation failed.',
                JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
                $validator->errors()->toArray()
            );
        }

        $validated = $validator->validated();

        // 1. Tạo Khách hàng đại lý
        $customer = \App\Models\Customer::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'address' => $validated['address'],
            'system_type_id' => $validated['system_type_id'],
            'contact_time' => $validated['contact_time'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'dealer_id' => $dealer->id,
            'status' => 'new',
        ]);

        // 2. Tạo Lead (Cơ hội kinh doanh)
        $lead = \App\Models\Lead::create([
            'code' => 'LD-' . strtoupper(uniqid()),
            'customer_id' => $customer->id,
            'dealer_id' => $dealer->id,
            'status' => 'new',
            'source' => 'api',
        ]);

        // 3. Ghi log Lead Timeline để lưu các thông tin bổ sung (Hệ thống, Giờ liên hệ, Ghi chú)
        \App\Models\LeadTimeline::create([
            'lead_id' => $lead->id,
            'event_type' => 'system_event',
            'new_status' => 'new',
            'content' => 'Khách hàng gửi yêu cầu tư vấn mới qua ứng dụng / web.',
            'payload' => [
                'system_type_id' => $validated['system_type_id'],
                'contact_time' => $validated['contact_time'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ],
            'actor_id' => $customer->id,
            'actor_type' => \App\Models\Customer::class,
        ]);

        $customer->loadMissing(['dealer', 'systemType']);
        app(DealerNotificationService::class)->notifyNewCustomerContact(
            $customer,
            $validated['notes'] ?? null,
        );

        activity('api')
            ->performedOn($lead)
            ->event('dealer_support_requested')
            ->withProperties([
                'channel' => 'api',
                'source' => 'api',
                'ip' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 255),
                'actor_name' => $customer->name,
                'actor_phone' => $customer->phone,
                'actor_email' => $customer->email,
                'dealer_id' => $dealer->id,
                'dealer_name' => $dealer->name,
                'customer_id' => $customer->id,
                'system_type_id' => $customer->system_type_id,
                'system_type_name' => $customer->systemType?->name,
                'contact_time' => $customer->contact_time,
                'notes' => $validated['notes'] ?? null,
            ])
            ->log('dealer_support_requested');

        return ApiResponse::success(
            ['customer_id' => $customer->id, 'lead_id' => $lead->id],
            'Gửi yêu cầu thành công. Đại lý sẽ sớm liên hệ với bạn.',
            'Request sent successfully.',
            JsonResponse::HTTP_CREATED
        );
    }

    protected function resolveAuthenticatedDealer(Request $request): ?Dealer
    {
        $user = $request->user();

        return $user instanceof Dealer ? $user : null;
    }
}
