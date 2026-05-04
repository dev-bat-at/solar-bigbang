<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportRequest;
use App\Models\SystemType;
use App\Support\Api\ApiResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

#[Group('Liên hệ & Hỗ trợ', 'Các API gửi yêu cầu liên hệ, báo giá từ hệ thống web/app gửi về cho Admin.', 81)]
class SystemQuoteRequestController extends Controller
{
    #[Endpoint(
        operationId: 'submitSystemQuoteRequest',
        title: 'Gửi yêu cầu báo giá theo hệ',
        description: 'Gửi yêu cầu liên hệ và báo giá cho một hệ cụ thể. Nếu hệ bật show công thức tính thì gửi theo các field đã cấu hình; nếu không thì gửi tiền điện, loại điện và tỷ lệ ngày đêm.'
    )]
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'email' => 'nullable|email|max:255',
            'system_type_id' => 'required|exists:system_types,id',
            'monthly_bill' => 'nullable|numeric|min:1',
            'phase_type' => 'nullable|string|in:1P,3P',
            'message' => 'nullable|string|max:2000',
            'request_payload' => 'nullable|array',
        ], [
            'required' => 'Trường :attribute là bắt buộc.',
            'exists' => ':attribute không tồn tại.',
            'in' => 'Giá trị :attribute không hợp lệ.',
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
        $systemType = SystemType::query()->findOrFail($validated['system_type_id']);

        if (! $systemType->quote_is_active) {
            return ApiResponse::error(
                'Hệ này hiện chưa bật nhận yêu cầu báo giá.',
                'This system is not accepting quote requests.',
                JsonResponse::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $requestPayload = $systemType->show_calculation_formula
            ? $this->buildCustomFieldPayload($request, $systemType)
            : $this->buildStandardSystemPayload($request, $systemType, $validated);

        $supportRequest = SupportRequest::create([
            'customer_name' => $validated['name'],
            'customer_phone' => $validated['phone'],
            'customer_email' => $validated['email'] ?? null,
            'request_type' => 'system_quote',
            'system_type_id' => $systemType->id,
            'customer_message' => $validated['message'] ?? null,
            'request_payload' => $requestPayload,
            'status' => 'new',
            'source' => 'api',
        ]);

        $supportRequest->loadMissing('systemType');

        $activity = activity('api')
            ->performedOn($supportRequest)
            ->event('system_quote_request_submitted')
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

        $activity->log('system_quote_request_submitted');

        return ApiResponse::success(
            ['support_request_id' => $supportRequest->id],
            'Gửi yêu cầu báo giá thành công. Chúng tôi sẽ sớm liên hệ lại với bạn.',
            'System quote request sent successfully.',
            JsonResponse::HTTP_CREATED
        );
    }

    protected function buildStandardSystemPayload(Request $request, SystemType $systemType, array $validated): array
    {
        if (blank($validated['monthly_bill'] ?? null)) {
            throw ValidationException::withMessages([
                'monthly_bill' => ['Vui lòng nhập tiền điện trung bình tháng.'],
            ]);
        }

        if ($systemType->quote_formula_type !== 'solar_pump' && blank($validated['phase_type'] ?? null)) {
            throw ValidationException::withMessages([
                'phase_type' => ['Vui lòng chọn loại điện 1P hoặc 3P.'],
            ]);
        }

        $fields = [
            [
                'key' => 'monthly_bill',
                'label_vi' => 'Tiền điện trung bình tháng',
                'label_en' => 'Average monthly electricity bill',
                'input_type' => 'number',
                'value' => (float) $validated['monthly_bill'],
            ],
        ];

        if ($systemType->quote_formula_type !== 'solar_pump') {
            $fields[] = [
                'key' => 'phase_type',
                'label_vi' => 'Loại điện',
                'label_en' => 'Power phase',
                'input_type' => 'text',
                'value' => strtoupper((string) $validated['phase_type']),
            ];
        }

        if ($systemType->quote_formula_type === 'bam_tai') {
            $ratioFields = collect($systemType->quote_ratio_fields)->values();
            $firstField = $ratioFields->get(0, []);
            $secondField = $ratioFields->get(1, []);

            $dayRatio = $this->resolveDynamicValue($request, (string) ($firstField['key'] ?? 'start_day'), $firstField['aliases'] ?? ['day_ratio']);
            $nightRatio = $this->resolveDynamicValue($request, (string) ($secondField['key'] ?? 'end_night'), $secondField['aliases'] ?? ['night_ratio']);

            if ($dayRatio === null && $nightRatio === null) {
                throw ValidationException::withMessages([
                    (string) ($firstField['key'] ?? 'start_day') => ['Vui lòng nhập tỷ lệ ngày hoặc tỷ lệ đêm.'],
                ]);
            }

            if ($dayRatio === null && $nightRatio !== null) {
                $dayRatio = round(max(0, min(100, 100 - $nightRatio)), 2);
            }

            if ($nightRatio === null && $dayRatio !== null) {
                $nightRatio = round(max(0, min(100, 100 - $dayRatio)), 2);
            }

            $fields[] = [
                'key' => (string) ($firstField['key'] ?? 'start_day'),
                'label_vi' => (string) ($firstField['label_vi'] ?? 'Tỷ lệ điện ban ngày (%)'),
                'label_en' => (string) ($firstField['label_en'] ?? 'Daytime power ratio (%)'),
                'input_type' => 'number',
                'value' => $dayRatio,
            ];
            $fields[] = [
                'key' => (string) ($secondField['key'] ?? 'end_night'),
                'label_vi' => (string) ($secondField['label_vi'] ?? 'Tỷ lệ điện ban đêm (%)'),
                'label_en' => (string) ($secondField['label_en'] ?? 'Nighttime power ratio (%)'),
                'input_type' => 'number',
                'value' => $nightRatio,
            ];
        }

        return [
            'mode' => 'system_quote_standard',
            'fields' => $fields,
        ];
    }

    protected function buildCustomFieldPayload(Request $request, SystemType $systemType): array
    {
        $configuredFields = collect($systemType->normalized_quote_request_fields);

        $fields = $configuredFields
            ->map(function (array $field) use ($request): array {
                return [
                    'key' => $field['key'],
                    'label_vi' => $field['label_vi'],
                    'label_en' => $field['label_en'],
                    'input_type' => $field['input_type'],
                    'required' => $field['required'],
                    'value' => $this->resolveRawDynamicValue($request, $field['key']),
                ];
            });

        $missingField = $fields->first(fn (array $field): bool => $field['required'] && blank($field['value']));

        if ($missingField !== null) {
            throw ValidationException::withMessages([
                $missingField['key'] => ['Trường '.$missingField['label_vi'].' là bắt buộc.'],
            ]);
        }

        return [
            'mode' => 'system_quote_custom',
            'fields' => $fields
                ->filter(fn (array $field): bool => ! blank($field['value']))
                ->values()
                ->all(),
        ];
    }

    protected function resolveDynamicValue(Request $request, string $key, array $aliases = []): ?float
    {
        $value = $this->resolveRawDynamicValue($request, $key, $aliases);

        if ($value === null || $value === '') {
            return null;
        }

        $value = (float) $value;

        if ($value <= 1) {
            $value *= 100;
        }

        return round(max(0, min(100, $value)), 2);
    }

    protected function resolveRawDynamicValue(Request $request, string $key, array $aliases = []): mixed
    {
        $requestPayload = $request->input('request_payload', []);
        $candidateKeys = collect([$key, ...$aliases])
            ->filter(fn (mixed $candidate): bool => is_string($candidate) && trim($candidate) !== '')
            ->map(fn (string $candidate): string => Str::of($candidate)->trim()->snake()->lower()->value())
            ->unique()
            ->values();

        foreach ($candidateKeys as $candidate) {
            if (is_array($requestPayload) && array_key_exists($candidate, $requestPayload) && $requestPayload[$candidate] !== '') {
                return $requestPayload[$candidate];
            }

            $input = $request->input($candidate);

            if ($input !== null && $input !== '') {
                return $input;
            }
        }

        return null;
    }
}
