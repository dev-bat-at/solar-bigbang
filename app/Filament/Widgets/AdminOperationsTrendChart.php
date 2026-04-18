<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\Project;
use App\Models\SupportRequest;
use App\Support\Dashboard\AdminDashboardMetrics;
use Filament\Widgets\ChartWidget;

class AdminOperationsTrendChart extends ChartWidget
{
    protected int | string | array $columnSpan = 'full';

    protected ?string $heading = 'Xu hướng vận hành 30 ngày';

    protected ?string $description = 'Theo dõi dữ liệu mới phát sinh mỗi ngày ở các luồng chính.';

    protected ?string $maxHeight = '320px';

    protected ?string $pollingInterval = '60s';

    protected function getData(): array
    {
        return [
            'labels' => AdminDashboardMetrics::labels(),
            'datasets' => [
                [
                    'label' => 'Khách hàng',
                    'data' => AdminDashboardMetrics::dailySeries(Customer::class),
                    'borderColor' => '#16a34a',
                    'backgroundColor' => 'rgba(22, 163, 74, 0.12)',
                    'tension' => 0.35,
                    'fill' => true,
                ],
                [
                    'label' => 'Lead',
                    'data' => AdminDashboardMetrics::dailySeries(Lead::class),
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.12)',
                    'tension' => 0.35,
                    'fill' => true,
                ],
                [
                    'label' => 'Liên hệ & báo giá',
                    'data' => AdminDashboardMetrics::dailySeries(SupportRequest::class),
                    'borderColor' => '#ef4444',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.12)',
                    'tension' => 0.35,
                    'fill' => true,
                ],
                [
                    'label' => 'Công trình',
                    'data' => AdminDashboardMetrics::dailySeries(Project::class),
                    'borderColor' => '#2563eb',
                    'backgroundColor' => 'rgba(37, 99, 235, 0.12)',
                    'tension' => 0.35,
                    'fill' => true,
                ],
            ],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'top',
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
            'maintainAspectRatio' => false,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
