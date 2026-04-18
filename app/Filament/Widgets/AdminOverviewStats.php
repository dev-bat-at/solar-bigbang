<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Dealers\DealerResource;
use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Resources\Posts\PostResource;
use App\Filament\Resources\Projects\ProjectResource;
use App\Filament\Resources\SupportRequests\SupportRequestResource;
use App\Models\Customer;
use App\Models\Dealer;
use App\Models\Lead;
use App\Models\Post;
use App\Models\Project;
use App\Models\SupportRequest;
use App\Support\Dashboard\AdminDashboardMetrics;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class AdminOverviewStats extends StatsOverviewWidget
{
    protected int | string | array $columnSpan = 'full';

    protected ?string $heading = 'Tổng quan vận hành';

    protected ?string $description = 'Những chỉ số cần theo dõi nhanh cho khu vực quản trị.';

    protected ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $dealerSeries = AdminDashboardMetrics::dailySeries(Dealer::class, 7);
        $customerSeries = AdminDashboardMetrics::dailySeries(Customer::class, 7);
        $leadSeries = AdminDashboardMetrics::dailySeries(Lead::class, 7);
        $supportSeries = AdminDashboardMetrics::dailySeries(SupportRequest::class, 7);
        $projectSeries = AdminDashboardMetrics::dailySeries(Project::class, 7);
        $postSeries = AdminDashboardMetrics::dailySeries(Post::class, 7);

        $quoteRequestScope = fn (Builder $query) => $query->whereIn('request_type', ['product_quote', 'system_quote']);

        return [
            Stat::make('Đại lý', number_format(AdminDashboardMetrics::count(Dealer::class)))
                ->description('Đã duyệt: ' . number_format(AdminDashboardMetrics::count(Dealer::class, fn (Builder $query) => $query->where('status', 'approved'))))
                ->descriptionIcon('heroicon-m-building-storefront', IconPosition::Before)
                ->color('info')
                ->chart($dealerSeries)
                ->url(DealerResource::getUrl()),

            Stat::make('Khách hàng', number_format(AdminDashboardMetrics::count(Customer::class)))
                ->description('Mới trong 30 ngày: ' . number_format(AdminDashboardMetrics::countSince(Customer::class, 30)))
                ->descriptionIcon('heroicon-m-user-group', IconPosition::Before)
                ->color('success')
                ->chart($customerSeries)
                ->url(CustomerResource::getUrl()),

            Stat::make('Lead', number_format(AdminDashboardMetrics::count(Lead::class)))
                ->description('Chốt thành công: ' . number_format(AdminDashboardMetrics::count(Lead::class, fn (Builder $query) => $query->where('status', 'won'))))
                ->descriptionIcon('heroicon-m-funnel', IconPosition::Before)
                ->color('warning')
                ->chart($leadSeries)
                ->url(LeadResource::getUrl()),

            Stat::make('Liên hệ & Báo giá', number_format(AdminDashboardMetrics::count(SupportRequest::class)))
                ->description('Yêu cầu báo giá: ' . number_format(AdminDashboardMetrics::count(SupportRequest::class, $quoteRequestScope)))
                ->descriptionIcon('heroicon-m-chat-bubble-left-right', IconPosition::Before)
                ->color('danger')
                ->chart($supportSeries)
                ->url(SupportRequestResource::getUrl()),

            Stat::make('Công trình', number_format(AdminDashboardMetrics::count(Project::class)))
                ->description('Chờ duyệt: ' . number_format(AdminDashboardMetrics::count(Project::class, fn (Builder $query) => $query->where('status', 'pending'))))
                ->descriptionIcon('heroicon-m-building-office-2', IconPosition::Before)
                ->color('primary')
                ->chart($projectSeries)
                ->url(ProjectResource::getUrl()),

            Stat::make('Bài viết', number_format(AdminDashboardMetrics::count(Post::class)))
                ->description('Đã đăng: ' . number_format(AdminDashboardMetrics::count(Post::class, fn (Builder $query) => $query->where('status', 'published'))))
                ->descriptionIcon('heroicon-m-document-text', IconPosition::Before)
                ->color('gray')
                ->chart($postSeries)
                ->url(PostResource::getUrl()),
        ];
    }
}
