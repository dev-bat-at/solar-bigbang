<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Dealer;
use App\Models\Lead;
use App\Models\LeadTimeline;
use App\Models\SystemType;
use App\Services\DealerNotificationService;
use App\Support\Api\ApiResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

#[Group('Liên hệ & Hỗ trợ', 'Các API gửi yêu cầu liên hệ, báo giá từ hệ thống web/app gửi trực tiếp đến đại lý.', 81)]
class SystemQuoteRequestController extends Controller
{
    protected const DEFAULT_CONTACT_TIME = 'Bất cứ lúc nào';

    #[Endpoint(
        operationId: 'submitSystemQuoteRequest',
        title: 'Gửi yêu cầu báo giá theo hệ',
        description: 'Gửi yêu cầu báo giá cho một hệ cụ thể và chuyển đến một hoặc nhiều đại lý. Nếu hệ bật show công thức tính thì gửi theo các field đã cấu hình; nếu không thì gửi tiền điện, loại điện và tỷ lệ ngày đêm.'
    )]
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'email' => 'nullable|email|max:255',
            'system_type_id' => 'required|exists:system_types,id',
            'dealer_ids' => ['required', 'array', 'min:1'],
            'dealer_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('dealers', 'id')->where(
                    fn ($query) => $query->whereIn('status', ['approved', 'active'])
                ),
            ],
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
        $dealerIds = collect($validated['dealer_ids'])
            ->map(fn (mixed $dealerId): int => (int) $dealerId)
            ->unique()
            ->values();

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

        $dealers = Dealer::query()
            ->whereIn('id', $dealerIds)
            ->whereIn('status', ['approved', 'active'])
            ->get()
            ->keyBy('id');

        if ($dealers->count() !== $dealerIds->count()) {
            return ApiResponse::error(
                'Một hoặc nhiều đại lý không tồn tại hoặc chưa được duyệt.',
                'One or more dealers are invalid or not approved.',
                JsonResponse::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $notes = $this->buildDealerNotes($validated['message'] ?? null, $requestPayload);
        $dealerNotificationService = app(DealerNotificationService::class);

        $submittedRequests = DB::transaction(function () use (
            $dealerIds,
            $dealers,
            $validated,
            $systemType,
            $requestPayload,
            $notes,
            $dealerNotificationService,
            $request
        ): array {
            $results = [];

            foreach ($dealerIds as $dealerId) {
                /** @var Dealer $dealer */
                $dealer = $dealers->get($dealerId);

                $customer = Customer::query()->create([
                    'dealer_id' => $dealer->id,
                    'name' => $validated['name'],
                    'phone' => $validated['phone'],
                    'email' => $validated['email'] ?? null,
                    'system_type_id' => $systemType->id,
                    'contact_time' => static::DEFAULT_CONTACT_TIME,
                    'notes' => $notes,
                    'status' => 'new',
                ]);

                $lead = Lead::query()->create([
                    'code' => 'LD-' . strtoupper(uniqid()),
                    'customer_id' => $customer->id,
                    'dealer_id' => $dealer->id,
                    'status' => 'new',
                    'source' => 'api',
                ]);

                LeadTimeline::query()->create([
                    'lead_id' => $lead->id,
                    'event_type' => 'system_event',
                    'new_status' => 'new',
                    'content' => 'Khách hàng gửi yêu cầu báo giá theo hệ từ website / ứng dụng.',
                    'payload' => $this->buildLeadTimelinePayload($validated, $systemType, $requestPayload),
                    'actor_id' => $customer->id,
                    'actor_type' => Customer::class,
                ]);

                $customer->loadMissing(['dealer', 'systemType']);
                $dealerNotificationService->notifyNewCustomerContact($customer, $notes);

                $activity = activity('api')
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
                        'system_type_id' => $systemType->id,
                        'system_type_name' => $systemType->name_vi ?: $systemType->name,
                        'notes' => $notes,
                        'request_payload' => $requestPayload,
                    ]);

                if ($request->user() !== null) {
                    $activity->causedBy($request->user());
                }

                $activity->log('dealer_support_requested');

                $results[] = [
                    'dealer_id' => $dealer->id,
                    'dealer_name' => $dealer->name,
                    'customer_id' => $customer->id,
                    'lead_id' => $lead->id,
                ];
            }

            return $results;
        });

        return ApiResponse::success(
            [
                'dealer_request_count' => count($submittedRequests),
                'dealer_requests' => $submittedRequests,
            ],
            'Gửi yêu cầu báo giá thành công. Các đại lý được chọn sẽ sớm liên hệ lại với bạn.',
            'System quote requests sent successfully.',
            JsonResponse::HTTP_CREATED
        );
    }

    protected function buildDealerNotes(?string $message, array $requestPayload): ?string
    {
        $lines = collect([
            filled($message) ? 'Nội dung khách gửi: '.trim($message) : null,
            filled(data_get($requestPayload, 'mode'))
                ? 'Biểu mẫu: '.($this->resolveRequestPayloadModeLabel((string) data_get($requestPayload, 'mode')) ?? (string) data_get($requestPayload, 'mode'))
                : null,
        ]);

        foreach ($this->summarizePayloadFields($requestPayload) as $fieldSummary) {
            $lines->push($fieldSummary);
        }

        $notes = $lines
            ->filter(fn (mixed $line): bool => is_string($line) && trim($line) !== '')
            ->implode(PHP_EOL);

        return $notes !== '' ? $notes : null;
    }

    protected function buildLeadTimelinePayload(array $validated, SystemType $systemType, array $requestPayload): array
    {
        return [
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'message' => $validated['message'] ?? null,
            'system_type_id' => $systemType->id,
            'system_type_name_vi' => $systemType->name_vi ?: $systemType->name,
            'system_type_name_en' => $systemType->name_en ?: $systemType->name_vi ?: $systemType->name,
            'contact_time' => static::DEFAULT_CONTACT_TIME,
            'request_payload' => $requestPayload,
            'request_payload_summary' => $this->summarizePayloadFields($requestPayload),
        ];
    }

    protected function summarizePayloadFields(array $requestPayload): array
    {
        $fields = data_get($requestPayload, 'fields', []);

        if (! is_array($fields)) {
            return [];
        }

        return collect($fields)
            ->filter(fn (mixed $field): bool => is_array($field))
            ->map(function (array $field): ?string {
                $label = trim((string) ($field['label_vi'] ?? $field['label_en'] ?? $field['key'] ?? ''));
                $key = trim((string) ($field['key'] ?? ''));
                $value = $field['value'] ?? null;

                if ($label === '' || $value === null || $value === '') {
                    return null;
                }

                return $label.': '.$this->formatSummaryValue($key, $label, $value);
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function resolveRequestPayloadModeLabel(string $mode): ?string
    {
        return match ($mode) {
            'system_quote_standard' => 'Báo giá theo hệ',
            'system_quote_custom' => 'Công thức tính',
            'day_night_ratio' => 'Tỷ lệ ngày đêm',
            'custom_fields' => 'Field tùy chỉnh',
            default => null,
        };
    }

    protected function formatSummaryValue(string $key, string $label, mixed $value): string
    {
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        if (is_numeric($value) && $this->shouldFormatAsMoney($key, $label)) {
            return number_format((float) $value, 0, ',', '.').' VNĐ';
        }

        if (is_numeric($value) && str_contains(mb_strtolower($label), '(%)')) {
            return rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
        }

        return (string) $value;
    }

    protected function shouldFormatAsMoney(string $key, string $label): bool
    {
        $normalizedKey = mb_strtolower(trim($key));
        $normalizedLabel = mb_strtolower(trim($label));

        return in_array($normalizedKey, ['monthly_bill', 'amount', 'price', 'cost', 'budget', 'total', 'estimated_value'], true)
            || str_contains($normalizedLabel, 'tiền')
            || str_contains($normalizedLabel, 'chi phí')
            || str_contains($normalizedLabel, 'ngân sách');
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
