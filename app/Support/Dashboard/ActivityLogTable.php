<?php

namespace App\Support\Dashboard;

use App\Filament\Pages\ActivityLogs;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;

class ActivityLogTable
{
    public static function configure(Table $table, bool $isDashboard = false): Table
    {
        return $table
            ->query(
                Activity::query()
                    ->with(['causer', 'subject'])
                    ->latest('created_at')
            )
            ->heading($isDashboard ? 'Hoạt động gần đây' : 'Nhật ký hoạt động hệ thống')
            ->description($isDashboard ? null : 'Theo dõi đầy đủ lịch sử thao tác của admin, người dùng và đại lý trong hệ thống.')
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginationPageOptions($isDashboard ? [5] : [10, 25, 50, 100])
            ->headerActions($isDashboard ? [
                Action::make('viewAll')
                    ->label('Xem tất cả')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(ActivityLogs::getUrl()),
            ] : [])
            ->columns([
                TextColumn::make('event')
                    ->label('Hành động')
                    ->badge()
                    ->icon(fn (?string $state): string => ActivityFeedFormatter::eventIcon($state))
                    ->formatStateUsing(fn (?string $state): string => ActivityFeedFormatter::eventLabel($state))
                    ->color(fn (?string $state): string => ActivityFeedFormatter::eventColor($state))
                    ->searchable(false),

                TextColumn::make('causer_type')
                    ->label('Người thực hiện')
                    ->icon('heroicon-m-user')
                    ->formatStateUsing(fn (Activity $record): string => ActivityFeedFormatter::actorName($record))
                    ->weight('semi-bold')
                    ->wrap(),

                TextColumn::make('subject_type')
                    ->label('Đối tượng')
                    ->formatStateUsing(fn (Activity $record): string => ActivityFeedFormatter::subjectLabel($record))
                    ->placeholder('-')
                    ->wrap(),

                TextColumn::make('detail')
                    ->label('Chi tiết')
                    ->state(fn (Activity $record): string => ActivityFeedFormatter::detailHtml($record, compact: $isDashboard)?->toHtml() ?? '-')
                    ->placeholder('-')
                    ->html()
                    ->grow(),

                TextColumn::make('created_at')
                    ->label('Thời gian')
                    ->icon('heroicon-m-clock')
                    ->since()
                    ->description(fn (Activity $record): ?string => $record->created_at?->format('d/m/Y H:i:s'))
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('event')
                    ->label('Hành động')
                    ->options(ActivityFeedFormatter::eventOptions())
                    ->native(false),

                SelectFilter::make('causer_type')
                    ->label('Người thực hiện')
                    ->attribute('causer_type')
                    ->options(ActivityFeedFormatter::causerTypeOptions())
                    ->native(false),

                SelectFilter::make('subject_type')
                    ->label('Đối tượng')
                    ->attribute('subject_type')
                    ->options(ActivityFeedFormatter::subjectTypeOptions())
                    ->native(false),
            ])
            ->emptyStateHeading('Chưa có hoạt động nào')
            ->emptyStateDescription('Khi có thao tác trong hệ thống, lịch sử sẽ xuất hiện ở đây.');
    }
}
