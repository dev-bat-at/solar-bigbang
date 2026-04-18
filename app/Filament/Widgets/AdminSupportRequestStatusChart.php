<?php

namespace App\Filament\Widgets;

use App\Models\SupportRequest;
use App\Support\Dashboard\AdminDashboardMetrics;
use Filament\Widgets\ChartWidget;

class AdminSupportRequestStatusChart extends ChartWidget
{
    protected int | string | array $columnSpan = 1;

    protected ?string $heading = 'Liên hệ & báo giá theo trạng thái';

    protected ?string $description = 'Theo dõi lượng yêu cầu mới, đã liên hệ, đã báo giá và hoàn tất.';

    protected ?string $maxHeight = '340px';

    protected ?string $pollingInterval = '60s';

    protected function getData(): array
    {
        $statuses = SupportRequest::statusOptions();

        return [
            'labels' => array_values($statuses),
            'datasets' => [
                [
                    'label' => 'Số lượng yêu cầu',
                    'data' => AdminDashboardMetrics::breakdown(SupportRequest::class, 'status', array_keys($statuses)),
                    'backgroundColor' => [
                        '#ef4444',
                        '#f59e0b',
                        '#16a34a',
                        '#2563eb',
                        '#9ca3af',
                    ],
                    'hoverOffset' => 8,
                ],
            ],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
            ],
            'cutout' => '62%',
            'maintainAspectRatio' => false,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
