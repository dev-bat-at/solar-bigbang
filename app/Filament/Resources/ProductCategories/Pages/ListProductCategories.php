<?php

namespace App\Filament\Resources\ProductCategories\Pages;

use App\Filament\Resources\ProductCategories\ProductCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProductCategories extends ListRecords
{
    protected static string $resource = ProductCategoryResource::class;

    public function getMaxContentWidth(): \Filament\Support\Enums\Width
    {
        return \Filament\Support\Enums\Width::Full;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tạo loại sản phẩm')
                ->modalWidth(\Filament\Support\Enums\Width::FourExtraLarge),
        ];
    }
}
