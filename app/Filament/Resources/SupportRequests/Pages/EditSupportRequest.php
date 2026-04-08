<?php

namespace App\Filament\Resources\SupportRequests\Pages;

use App\Filament\Resources\SupportRequests\SupportRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSupportRequest extends EditRecord
{
    protected static string $resource = SupportRequestResource::class;

    public function getMaxContentWidth(): \Filament\Support\Enums\Width
    {
        return \Filament\Support\Enums\Width::Full;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
