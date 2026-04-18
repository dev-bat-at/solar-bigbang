<?php

namespace App\Filament\Pages;

use App\Support\Dashboard\ActivityLogTable;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables;
use Filament\Tables\Table;

class ActivityLogs extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'Nhật ký hoạt động';

    protected static ?string $title = 'Nhật ký hoạt động';

    protected static string | \UnitEnum | null $navigationGroup = 'Cấu hình';

    protected static ?int $navigationSort = 3;

    protected static string $routePath = 'activity-logs';

    public ?array $tableFilters = null;

    public ?string $tableSort = null;

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    public function table(Table $table): Table
    {
        return ActivityLogTable::configure($table, isDashboard: false);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                EmbeddedTable::make(),
            ]);
    }
}
