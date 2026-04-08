<?php

namespace App\Support;

use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Projects\ProjectResource;
use App\Filament\Resources\SupportRequests\SupportRequestResource;
use App\Models\AdminUser;
use App\Models\Customer;
use App\Models\Project;
use App\Models\SupportRequest;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Cache;

class AdminTopbarAlerts
{
    /**
     * @return array<string>
     */
    public static function types(): array
    {
        return ['customers', 'support_requests', 'projects'];
    }

    public static function isValidType(string $type): bool
    {
        return in_array($type, static::types(), true);
    }

    public static function markAsViewed(AdminUser $user, string $type): void
    {
        if (! static::isValidType($type)) {
            return;
        }

        Cache::forever(static::cacheKey($user, $type), now()->toDateTimeString());
    }

    public static function destinationUrl(string $type): string
    {
        return match ($type) {
            'customers' => CustomerResource::getUrl(),
            'support_requests' => SupportRequestResource::getUrl('index', [
                'tableFilters' => [
                    'status' => [
                        'value' => 'new',
                    ],
                ],
            ]),
            'projects' => ProjectResource::getUrl('index', [
                'tableFilters' => [
                    'status' => [
                        'value' => 'pending',
                    ],
                ],
            ]),
            default => CustomerResource::getUrl(),
        };
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function itemsFor(?AdminUser $user): array
    {
        $customersTotal = Customer::query()->count();
        $customersUnread = static::unreadCustomersCount($user);

        $supportRequestsBaseQuery = SupportRequest::query()->where('status', 'new');
        $supportRequestsTotal = (clone $supportRequestsBaseQuery)->count();
        $supportRequestsUnread = static::unreadCountForQuery(clone $supportRequestsBaseQuery, $user, 'support_requests');

        $projectsBaseQuery = Project::query()->where('status', 'pending');
        $projectsTotal = (clone $projectsBaseQuery)->count();
        $projectsUnread = static::unreadCountForQuery(clone $projectsBaseQuery, $user, 'projects');

        return [
            [
                'type' => 'customers',
                'label' => 'Liên hệ',
                'description' => 'Khách hàng đã tạo trong hệ thống',
                'total_count' => $customersTotal,
                'unread_count' => $customersUnread,
                'icon' => Heroicon::OutlinedUsers,
                'color' => 'info',
                'url' => CustomerResource::canViewAny() ? route('admin.topbar-alerts.redirect', ['type' => 'customers']) : null,
            ],
            [
                'type' => 'support_requests',
                'label' => 'Liên hệ & báo giá',
                'description' => 'Yêu cầu mới gửi trực tiếp cho admin',
                'total_count' => $supportRequestsTotal,
                'unread_count' => $supportRequestsUnread,
                'icon' => Heroicon::OutlinedClipboardDocumentList,
                'color' => 'warning',
                'url' => SupportRequestResource::canViewAny() ? route('admin.topbar-alerts.redirect', ['type' => 'support_requests']) : null,
            ],
            [
                'type' => 'projects',
                'label' => 'Công trình chờ duyệt',
                'description' => 'Công trình đang chờ phê duyệt',
                'total_count' => $projectsTotal,
                'unread_count' => $projectsUnread,
                'icon' => Heroicon::OutlinedBuildingOffice2,
                'color' => 'danger',
                'url' => ProjectResource::canViewAny() ? route('admin.topbar-alerts.redirect', ['type' => 'projects']) : null,
            ],
        ];
    }

    protected static function unreadCustomersCount(?AdminUser $user): int
    {
        $query = Customer::query();

        return static::unreadCountForQuery($query, $user, 'customers');
    }

    protected static function unreadCountForQuery($query, ?AdminUser $user, string $type): int
    {
        $lastViewedAt = $user ? Cache::get(static::cacheKey($user, $type)) : null;

        if (blank($lastViewedAt)) {
            return $query->count();
        }

        return $query->where('created_at', '>', $lastViewedAt)->count();
    }

    protected static function cacheKey(AdminUser $user, string $type): string
    {
        return "admin_topbar_alert_seen:{$user->getKey()}:{$type}";
    }
}
