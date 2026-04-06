<?php

namespace App\Filament\Resources\SystemTypes\Pages;

use App\Filament\Resources\SystemTypes\SystemTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

class ManageSystemTypes extends ListRecords
{
    protected static string $resource = SystemTypeResource::class;

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tạo hệ mới')
                ->modalHeading('Tạo mới hệ thiết kế')
                ->modalWidth(Width::FourExtraLarge),
        ];
    }
}
