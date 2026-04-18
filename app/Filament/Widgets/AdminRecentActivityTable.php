<?php

namespace App\Filament\Widgets;

use App\Support\Dashboard\ActivityLogTable;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class AdminRecentActivityTable extends TableWidget
{
    protected static bool $isLazy = false;

    protected int | string | array $columnSpan = 'full';

    protected ?string $pollingInterval = '30s';

    protected int | string | null $defaultTableRecordsPerPageSelectOption = 5;

    public function table(Table $table): Table
    {
        return ActivityLogTable::configure($table, isDashboard: true);
    }
}
