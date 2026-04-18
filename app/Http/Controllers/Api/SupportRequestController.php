<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportRequest;
use App\Support\Api\ApiResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

#[Group('Liên hệ & Hỗ trợ', 'Các API gửi yêu cầu liên hệ, báo giá từ hệ thống web/app gửi về cho Admin.', 80)]
class SupportRequestController extends Controller
{
    #[Endpoint(
        operationId: 'submitSupportRequest',
        title: 'Gửi yêu cầu Liên hệ / Báo giá',
        description: 'Đăng ký nhận báo giá theo sản phẩm, báo giá theo hệ thống, hoặc gửi liên hệ chung đến Admin.'
    )]
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:1000',
            'type' => ['required', 'string', Rule::in(array_keys(SupportRequest::requestTypeOptions()))],
            'product_id' => 'nullable|required_if:type,product_quote|exists:products,id',
            'system_type_id' => 'nullable|required_if:type,system_quote|exists:system_types,id',
            'message' => 'nullable|string|max:2000',
        ], [
            'required' => 'Trường :attribute là bắt buộc.',
            'required_if' => 'Trường :attribute là bắt buộc đối với loại yêu cầu này.',
            'in' => 'Loại yêu cầu không hợp lệ.',
            'exists' => ':attribute không tồn tại.',
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

        $supportRequest = SupportRequest::create([
            'customer_name' => $validated['name'],
            'customer_phone' => $validated['phone'],
            'customer_email' => $validated['email'] ?? null,
            'customer_address' => $validated['address'] ?? null,
            'request_type' => $validated['type'],
            'product_id' => $validated['product_id'] ?? null,
            'system_type_id' => $validated['system_type_id'] ?? null,
            'customer_message' => $validated['message'] ?? null,
            'status' => 'new',
            'source' => 'api',
        ]);

        $supportRequest->loadMissing(['product', 'systemType']);

        $activity = activity('api')
            ->performedOn($supportRequest)
            ->event('support_request_submitted')
            ->withProperties([
                'channel' => 'api',
                'source' => $supportRequest->source,
                'ip' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 255),
                'actor_name' => $supportRequest->customer_name,
                'actor_phone' => $supportRequest->customer_phone,
                'actor_email' => $supportRequest->customer_email,
                'request_type' => $supportRequest->request_type,
                'request_type_label' => $supportRequest->request_type_label,
                'target_label' => $supportRequest->target_label,
                'support_request_id' => $supportRequest->id,
            ]);

        if ($request->user() !== null) {
            $activity->causedBy($request->user());
        }

        $activity->log('support_request_submitted');

        return ApiResponse::success(
            ['support_request_id' => $supportRequest->id],
            'Gửi yêu cầu thành công. Chúng tôi sẽ sớm liên hệ lại với bạn.',
            'Support request sent successfully.',
            JsonResponse::HTTP_CREATED
        );
    }
}
