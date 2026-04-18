<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AdminLeadPipelineChart;
use App\Filament\Widgets\AdminOperationsTrendChart;
use App\Filament\Widgets\AdminOverviewStats;
use App\Filament\Widgets\AdminRecentActivityTable;
use App\Filament\Widgets\AdminSupportRequestStatusChart;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Trang chủ';
    protected static ?string $title = 'Bảng điều khiển';

    public function getWidgets(): array
    {
        return [
            AdminOverviewStats::class,
            AdminOperationsTrendChart::class,
            AdminLeadPipelineChart::class,
            AdminSupportRequestStatusChart::class,
            AdminRecentActivityTable::class,
        ];
    }
}
