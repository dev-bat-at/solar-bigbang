<?php

namespace App\Support\Dashboard;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

class ActivityFeedFormatter
{
    public static function actorName(Activity $activity): string
    {
        $properties = $activity->properties ?? collect();

        return static::resolveModelLabel($activity->causer)
            ?? $properties->get('actor_name')
            ?? $properties->get('actor_phone')
            ?? 'Hệ thống';
    }

    public static function subjectLabel(Activity $activity): ?string
    {
        $subjectType = static::resolveSubjectTypeLabel($activity);
        $subjectName = static::resolveSubjectName($activity);

        return trim($subjectType . ' ' . $subjectName) ?: null;
    }

    public static function detailSummary(Activity $activity): ?string
    {
        $items = static::resolveContextItems($activity);

        if ($items === []) {
            return null;
        }

        return collect($items)
            ->map(fn (array $item): string => "{$item['label']}: {$item['value']}")
            ->implode(' | ');
    }

    public static function detailHtml(Activity $activity, bool $compact = false): ?HtmlString
    {
        $allItems = static::resolveContextItems($activity);

        if ($allItems === []) {
            return null;
        }

        $items = $compact ? static::compactItems($activity, $allItems) : $allItems;
        $remaining = max(count($allItems) - count($items), 0);

        $segments = collect($items)
            ->map(function (array $item): string {
                $label = e($item['label']);
                $value = e($item['value']);

                return "<span class=\"inline-flex max-w-full items-baseline gap-1 align-middle\"><span class=\"font-medium text-gray-950 dark:text-white\">{$label}:</span><span class=\"truncate text-gray-600 dark:text-gray-300\">{$value}</span></span>";
            })
            ->implode('<span class="mx-2 text-gray-400 dark:text-gray-500">•</span>');

        if ($remaining > 0) {
            $segments .= '<span class="mx-2 text-gray-400 dark:text-gray-500">•</span><span class="text-xs text-gray-500 dark:text-gray-400">+' . $remaining . ' khác</span>';
        }

        $containerClass = $compact
            ? 'block max-w-[24rem] overflow-hidden text-ellipsis whitespace-nowrap text-sm leading-5'
            : 'block max-w-[38rem] overflow-hidden text-ellipsis whitespace-nowrap text-sm leading-5';

        return new HtmlString('<div class="' . $containerClass . '">' . $segments . '</div>');
    }

    public static function eventLabel(?string $event): string
    {
        return static::resolveEventLabel($event);
    }

    public static function eventColor(?string $event): string
    {
        return static::resolveColor($event);
    }

    public static function eventIcon(?string $event): string
    {
        return match ($event) {
            'created' => 'heroicon-m-plus-circle',
            'updated' => 'heroicon-m-pencil-square',
            'deleted' => 'heroicon-m-trash',
            'restored' => 'heroicon-m-arrow-path',
            'registered' => 'heroicon-m-user-plus',
            'login' => 'heroicon-m-arrow-right-circle',
            'logout' => 'heroicon-m-arrow-left-circle',
            'support_request_submitted' => 'heroicon-m-chat-bubble-left-right',
            'dealer_support_requested' => 'heroicon-m-megaphone',
            'customer_status_updated' => 'heroicon-m-arrows-right-left',
            default => 'heroicon-m-bolt',
        };
    }

    public static function eventOptions(): array
    {
        return [
            'created' => 'Tạo mới',
            'updated' => 'Cập nhật',
            'deleted' => 'Xóa',
            'restored' => 'Khôi phục',
            'registered' => 'Đăng ký',
            'login' => 'Đăng nhập',
            'logout' => 'Đăng xuất',
            'support_request_submitted' => 'Gửi báo giá',
            'dealer_support_requested' => 'Yêu cầu đại lý',
            'customer_status_updated' => 'Đổi trạng thái',
        ];
    }

    public static function causerTypeOptions(): array
    {
        return [
            'App\\Models\\AdminUser' => 'Quản trị viên',
            'App\\Models\\User' => 'Người dùng',
            'App\\Models\\Dealer' => 'Đại lý',
        ];
    }

    public static function subjectTypeOptions(): array
    {
        return [
            'App\\Models\\AdminUser' => 'Tài khoản quản trị',
            'App\\Models\\User' => 'Người dùng',
            'App\\Models\\Dealer' => 'Đại lý',
            'App\\Models\\Customer' => 'Khách hàng',
            'App\\Models\\Lead' => 'Lead',
            'App\\Models\\SupportRequest' => 'Yêu cầu liên hệ',
            'App\\Models\\Project' => 'Công trình',
            'App\\Models\\Post' => 'Bài viết',
            'App\\Models\\Product' => 'Sản phẩm',
            'App\\Models\\ProductCategory' => 'Loại sản phẩm',
            'App\\Models\\SystemType' => 'Hệ thống',
            'App\\Models\\Province' => 'Địa phương',
            'App\\Models\\Tag' => 'Tag',
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public static function recent(int $limit = 12): Collection
    {
        return Activity::query()
            ->with(['causer', 'subject'])
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (Activity $activity): array => static::format($activity));
    }

    /**
     * @return array<string, mixed>
     */
    public static function format(Activity $activity): array
    {
        $event = $activity->event ?: $activity->description;
        $verb = static::resolveVerb($event);
        $actorName = static::actorName($activity);
        $subjectType = static::resolveSubjectTypeLabel($activity);
        $subjectName = static::resolveSubjectName($activity);

        $message = match ($event) {
            'registered' => "{$actorName} đã đăng ký tài khoản",
            'login' => "{$actorName} đã đăng nhập hệ thống",
            'logout' => "{$actorName} đã đăng xuất hệ thống",
            'support_request_submitted' => "{$actorName} đã gửi yêu cầu liên hệ / báo giá",
            'dealer_support_requested' => "{$actorName} đã gửi yêu cầu tư vấn đến đại lý",
            'customer_status_updated' => "{$actorName} đã cập nhật trạng thái khách hàng",
            default => trim("{$actorName} đã {$verb} {$subjectType} {$subjectName}"),
        };

        return [
            'id' => $activity->id,
            'message' => preg_replace('/\s+/', ' ', trim($message)),
            'context' => static::detailSummary($activity),
            'event' => $event,
            'event_label' => static::eventLabel($event),
            'badge_color' => static::eventColor($event),
            'time' => $activity->created_at?->diffForHumans(),
            'created_at' => $activity->created_at?->format('d/m/Y H:i:s'),
        ];
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    protected static function resolveContextItems(Activity $activity): array
    {
        $event = $activity->event ?: $activity->description;
        $properties = $activity->properties ?? collect();

        if (in_array($event, ['login', 'logout'], true)) {
            return static::items([
                ['Phiên đăng nhập', static::formatGuard($properties->get('guard'))],
                ['IP', static::formatValue($properties->get('ip'))],
            ]);
        }

        if ($event === 'registered') {
            return static::items([
                ['Số điện thoại', static::formatValue($properties->get('phone'))],
                ['Email', static::formatValue($properties->get('email'))],
                ['IP', static::formatValue($properties->get('ip'))],
            ]);
        }

        if ($event === 'support_request_submitted') {
            return static::items([
                ['Loại yêu cầu', static::formatValue($properties->get('request_type_label'))],
                ['Đối tượng', static::formatValue($properties->get('target_label'))],
                ['Số điện thoại', static::formatValue($properties->get('actor_phone'))],
            ]);
        }

        if ($event === 'dealer_support_requested') {
            return static::items([
                ['Đại lý tiếp nhận', static::formatValue($properties->get('dealer_name'))],
                ['Hệ thống quan tâm', static::formatValue($properties->get('system_type_name'))],
                ['Thời gian liên hệ', static::formatValue($properties->get('contact_time'))],
                ['Số điện thoại', static::formatValue($properties->get('actor_phone'))],
            ]);
        }

        if ($event === 'customer_status_updated') {
            return static::items([
                ['Trạng thái', static::formatStatusTransition($properties->get('old_status'), $properties->get('new_status'))],
                ['Đại lý', static::formatValue($properties->get('dealer_name'))],
            ]);
        }

        $changes = $activity->changes();
        $attributes = collect($changes->get('attributes', []));
        $old = collect($changes->get('old', []));

        if ($old->has('status') && $attributes->has('status')) {
            return static::items([
                ['Trạng thái', static::formatStatusTransition($old->get('status'), $attributes->get('status'))],
            ]);
        }

        $changedFields = $attributes
            ->keys()
            ->reject(fn (string $field): bool => in_array($field, ['updated_at', 'created_at', 'deleted_at', 'password', 'remember_token'], true))
            ->map(fn (string $field): string => static::fieldLabel($field))
            ->take(4)
            ->values()
            ->all();

        if ($changedFields !== []) {
            return static::items([
                ['Nội dung thay đổi', implode(', ', $changedFields)],
            ]);
        }

        return [];
    }

    protected static function resolveModelLabel(?Model $model): ?string
    {
        if (! $model) {
            return null;
        }

        foreach (['name', 'title', 'customer_name', 'email', 'phone', 'code', 'slug'] as $attribute) {
            $value = data_get($model, $attribute);

            if (filled($value)) {
                return (string) $value;
            }
        }

        return class_basename($model) . ' #' . $model->getKey();
    }

    protected static function resolveSubjectTypeLabel(Activity $activity): string
    {
        $type = $activity->subject_type ? class_basename($activity->subject_type) : null;

        return match ($type) {
            'AdminUser' => 'tài khoản quản trị',
            'User' => 'người dùng',
            'Dealer' => 'đại lý',
            'Customer' => 'khách hàng',
            'Lead' => 'lead',
            'SupportRequest' => 'yêu cầu liên hệ',
            'Project' => 'công trình',
            'Post' => 'bài viết',
            'Product' => 'sản phẩm',
            'ProductCategory' => 'loại sản phẩm',
            'SystemType' => 'hệ thống',
            'SystemSetting' => 'cấu hình hệ thống',
            'Province' => 'địa phương',
            'Tag' => 'tag',
            default => $type ? Str::of($type)->headline()->lower()->toString() : 'hệ thống',
        };
    }

    protected static function resolveSubjectName(Activity $activity): string
    {
        if ($activity->subject) {
            return static::resolveQuotedLabel(static::resolveModelLabel($activity->subject));
        }

        $attributes = collect($activity->properties?->get('attributes', []));

        foreach (['name', 'title', 'customer_name', 'email', 'phone', 'code', 'slug'] as $attribute) {
            if (filled($attributes->get($attribute))) {
                return static::resolveQuotedLabel((string) $attributes->get($attribute));
            }
        }

        if ($activity->subject_id) {
            return '#' . $activity->subject_id;
        }

        return '';
    }

    protected static function resolveQuotedLabel(?string $label): string
    {
        return filled($label) ? '"' . $label . '"' : '';
    }

    protected static function resolveVerb(?string $event): string
    {
        return match ($event) {
            'created' => 'tạo mới',
            'updated' => 'cập nhật',
            'deleted' => 'xóa',
            'restored' => 'khôi phục',
            'registered' => 'đăng ký',
            'login' => 'đăng nhập',
            'logout' => 'đăng xuất',
            'support_request_submitted' => 'gửi yêu cầu',
            'dealer_support_requested' => 'gửi yêu cầu tư vấn',
            'customer_status_updated' => 'cập nhật trạng thái',
            default => 'thao tác với',
        };
    }

    protected static function resolveEventLabel(?string $event): string
    {
        return match ($event) {
            'created' => 'Tạo mới',
            'updated' => 'Cập nhật',
            'deleted' => 'Xóa',
            'restored' => 'Khôi phục',
            'registered' => 'Đăng ký',
            'login' => 'Đăng nhập',
            'logout' => 'Đăng xuất',
            'support_request_submitted' => 'Gửi báo giá',
            'dealer_support_requested' => 'Yêu cầu đại lý',
            'customer_status_updated' => 'Đổi trạng thái',
            default => 'Hoạt động',
        };
    }

    protected static function resolveColor(?string $event): string
    {
        return match ($event) {
            'created', 'restored', 'login', 'registered' => 'success',
            'updated' => 'info',
            'support_request_submitted', 'dealer_support_requested', 'customer_status_updated' => 'warning',
            'deleted' => 'danger',
            'logout' => 'gray',
            default => 'warning',
        };
    }

    protected static function formatValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'Có' : 'Không';
        }

        if (is_scalar($value) || $value === null) {
            return (string) $value;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '...';
    }

    protected static function formatChannel(mixed $channel): string
    {
        return match ((string) $channel) {
            'api' => 'API / Ứng dụng',
            'web' => 'Web quản trị',
            default => static::formatValue($channel),
        };
    }

    protected static function formatGuard(mixed $guard): string
    {
        return match ((string) $guard) {
            'sanctum-user' => 'Người dùng ứng dụng',
            'sanctum-dealer' => 'Đại lý ứng dụng',
            'web' => 'Phiên web',
            'admin' => 'Quản trị viên',
            default => static::formatValue($guard),
        };
    }

    protected static function formatStatusTransition(mixed $oldStatus, mixed $newStatus): string
    {
        $oldLabel = static::statusLabel($oldStatus);
        $newLabel = static::statusLabel($newStatus);

        if ($oldLabel === '' && $newLabel === '') {
            return '';
        }

        if ($oldLabel === '') {
            return $newLabel;
        }

        if ($newLabel === '') {
            return $oldLabel;
        }

        return "{$oldLabel} -> {$newLabel}";
    }

    protected static function statusLabel(mixed $value): string
    {
        return match ((string) $value) {
            'new' => 'Mới',
            'processing' => 'Đang xử lý',
            'completed' => 'Hoàn thành',
            'contacted' => 'Đã liên hệ',
            'quoted' => 'Đã gửi báo giá',
            'resolved' => 'Hoàn tất',
            'cancelled' => 'Đã hủy',
            'pending' => 'Chờ xử lý',
            'approved' => 'Đã duyệt',
            'active' => 'Đang hoạt động',
            'inactive' => 'Tạm ngưng',
            default => static::formatValue($value),
        };
    }

    protected static function fieldLabel(string $field): string
    {
        return match ($field) {
            'name' => 'Tên',
            'title' => 'Tiêu đề',
            'slug' => 'Slug',
            'code' => 'Mã',
            'phone' => 'Số điện thoại',
            'email' => 'Email',
            'address' => 'Địa chỉ',
            'status' => 'Trạng thái',
            'avatar', 'avatar_url' => 'Ảnh đại diện',
            'province_id' => 'Tỉnh / Thành',
            'dealer_id' => 'Đại lý',
            'customer_id' => 'Khách hàng',
            'system_type_id' => 'Loại hệ thống',
            'product_id' => 'Sản phẩm',
            'request_type' => 'Loại yêu cầu',
            'customer_name' => 'Tên khách hàng',
            'customer_phone' => 'Số điện thoại khách hàng',
            'customer_email' => 'Email khách hàng',
            'customer_address' => 'Địa chỉ khách hàng',
            'customer_message' => 'Nội dung khách hàng',
            'source' => 'Nguồn',
            'priority_order' => 'Thứ tự ưu tiên',
            default => Str::of($field)->replace('_', ' ')->headline()->toString(),
        };
    }

    /**
     * @param  array<int, array{0: string, 1: string}>  $items
     * @return array<int, array{label: string, value: string}>
     */
    protected static function items(array $items): array
    {
        return collect($items)
            ->filter(fn (array $item): bool => filled($item[1]))
            ->map(fn (array $item): array => ['label' => $item[0], 'value' => $item[1]])
            ->unique(fn (array $item): string => $item['label'] . '|' . $item['value'])
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array{label: string, value: string}>  $items
     * @return array<int, array{label: string, value: string}>
     */
    protected static function compactItems(Activity $activity, array $items): array
    {
        $event = $activity->event ?: $activity->description;

        $priority = match ($event) {
            'login', 'logout' => ['IP', 'Phiên đăng nhập'],
            'registered' => ['Số điện thoại', 'Email', 'IP'],
            'support_request_submitted' => ['Loại yêu cầu', 'Đối tượng', 'Số điện thoại'],
            'dealer_support_requested' => ['Đại lý tiếp nhận', 'Hệ thống quan tâm', 'Thời gian liên hệ'],
            'customer_status_updated' => ['Trạng thái', 'Đại lý'],
            default => [],
        };

        if ($priority === []) {
            return array_slice($items, 0, 2);
        }

        $ordered = collect($priority)
            ->map(function (string $label) use ($items): ?array {
                return collect($items)->firstWhere('label', $label);
            })
            ->filter()
            ->values()
            ->all();

        if ($ordered !== []) {
            return array_slice($ordered, 0, 2);
        }

        return array_slice($items, 0, 2);
    }
}
