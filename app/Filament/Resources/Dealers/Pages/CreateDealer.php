<?php

namespace App\Filament\Resources\Dealers\Pages;

use App\Filament\Resources\Dealers\DealerResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDealer extends CreateRecord
{
    protected static string $resource = DealerResource::class;

    public function getMaxContentWidth(): \Filament\Support\Enums\Width
    {
        return \Filament\Support\Enums\Width::Full;
    }
}
