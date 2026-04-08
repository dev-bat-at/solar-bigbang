<?php

namespace App\Filament\Resources\Dealers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class DealersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\ImageColumn::make('avatar')
                    ->label('Ảnh')
                    ->disk('root_public')
                    ->circular(),
                \Filament\Tables\Columns\TextColumn::make('name')
                    ->label('Tên đại lý')
                    ->searchable()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('code')
                    ->label('Mã đại lý')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('phone')
                    ->label('Số điện thoại')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Nháp',
                        'pending' => 'Chờ duyệt',
                        'approved' => 'Đã duyệt',
                        'rejected' => 'Bị từ chối',
                        'inactive' => 'Ngưng hoạt động',
                        'suspended' => 'Tạm đình chỉ',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        'rejected', 'suspended' => 'danger',
                        default => 'gray',
                    }),
                \Filament\Tables\Columns\TextColumn::make('priority_order')
                    ->label('Thứ tự ưu tiên')
                    ->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
