<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Product;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ProductInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('code'),
                TextEntry::make('name_vi'),
                TextEntry::make('name_en')
                    ->placeholder('-'),
                TextEntry::make('slug'),
                TextEntry::make('tagline_vi')
                    ->placeholder('-'),
                TextEntry::make('tagline_en')
                    ->placeholder('-'),
                TextEntry::make('status'),
                IconEntry::make('is_best_seller')
                    ->boolean(),
                TextEntry::make('price')
                    ->money()
                    ->placeholder('-'),
                TextEntry::make('price_unit_vi')
                    ->placeholder('-'),
                TextEntry::make('price_unit_en')
                    ->placeholder('-'),
                TextEntry::make('power')
                    ->placeholder('-'),
                TextEntry::make('efficiency')
                    ->placeholder('-'),
                TextEntry::make('warranty')
                    ->placeholder('-'),
                TextEntry::make('description_vi')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('description_en')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (Product $record): bool => $record->trashed()),
            ]);
    }
}
