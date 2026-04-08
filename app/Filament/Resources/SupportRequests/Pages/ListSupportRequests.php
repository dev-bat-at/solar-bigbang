<?php

namespace App\Filament\Resources\SupportRequests\Pages;

use App\Filament\Resources\SupportRequests\SupportRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSupportRequests extends ListRecords
{
    protected static string $resource = SupportRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Thêm yêu cầu'),
        ];
    }
}
