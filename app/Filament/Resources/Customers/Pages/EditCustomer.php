<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions;

class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;

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
