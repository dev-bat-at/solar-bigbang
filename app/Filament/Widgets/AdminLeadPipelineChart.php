<?php

namespace App\Filament\Widgets;

use App\Models\Lead;
use App\Support\Dashboard\AdminDashboardMetrics;
use Filament\Widgets\ChartWidget;

class AdminLeadPipelineChart extends ChartWidget
{
    protected int | string | array $columnSpan = 1;

    protected ?string $heading = 'Phễu lead hiện tại';

    protected ?string $description = 'Nhìn nhanh lead đang nằm ở giai đoạn nào trong hành trình xử lý.';

    protected ?string $maxHeight = '340px';

    protected ?string $pollingInterval = '60s';

    protected function getData(): array
    {
        $statuses = $this->getLeadStatuses();

        return [
            'labels' => array_values($statuses),
            'datasets' => [
                [
                    'label' => 'Số lượng lead',
                    'data' => AdminDashboardMetrics::breakdown(Lead::class, 'status', array_keys($statuses)),
                    'backgroundColor' => [
                        '#3b82f6',
                        '#0ea5e9',
                        '#f59e0b',
                        '#8b5cf6',
                        '#f97316',
                        '#16a34a',
                        '#ef4444',
                        '#6b7280',
                        '#14b8a6',
                    ],
                    'borderRadius' => 8,
                ],
            ],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'x' => [
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
        return 'bar';
    }

    protected function getLeadStatuses(): array
    {
        return [
            'new' => 'Mới',
            'assigned' => 'Đã gán',
            'contacting' => 'Đang liên hệ',
            'quoted' => 'Đã báo giá',
            'negotiating' => 'Đang thương thảo',
            'won' => 'Chốt thành công',
            'lost' => 'Thất bại',
            'expired' => 'Hết hạn',
            'reopened' => 'Mở lại',
        ];
    }
}
