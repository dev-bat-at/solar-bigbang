<?php

namespace App\Filament\Resources\Provinces\Pages;

use App\Filament\Resources\Provinces\ProvinceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProvinces extends ListRecords
{
    protected static string $resource = ProvinceResource::class;

    public function getMaxContentWidth(): \Filament\Support\Enums\Width
    {
        return \Filament\Support\Enums\Width::Full;
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('sync_json')
                ->label('Cập nhật dữ liệu từ JSON')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Cập nhật dữ liệu Tỉnh / Thành')
                ->modalDescription('Thao tác này sẽ làm mới toàn bộ danh sách Tỉnh, Quận/Huyện, Phường/Xã từ file JSON trong hệ thống. Bạn có chắc chắn muốn thực hiện?')
                ->modalSubmitActionLabel('Bắt đầu cập nhật')
                ->action(function () {
                    try {
                        (new \Database\Seeders\ImportLocationDataSeeder())->run();
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Cập nhật thành công!')
                            ->body('Dữ liệu Tỉnh/Thành đã được đồng bộ từ JSON.')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('Lỗi cập nhật!')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
