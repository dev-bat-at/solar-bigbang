<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->required(),
                TextInput::make('name_vi')
                    ->required(),
                TextInput::make('name_en'),
                TextInput::make('slug')
                    ->required(),
                TextInput::make('tagline_vi'),
                TextInput::make('tagline_en'),
                TextInput::make('status')
                    ->required()
                    ->default('draft'),
                Toggle::make('is_best_seller')
                    ->required(),
                TextInput::make('images'),
                TextInput::make('price')
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('price_unit_vi'),
                TextInput::make('price_unit_en'),
                TextInput::make('power'),
                TextInput::make('efficiency'),
                TextInput::make('warranty'),
                Textarea::make('description_vi')
                    ->columnSpanFull(),
                Textarea::make('description_en')
                    ->columnSpanFull(),
                TextInput::make('specifications'),
                TextInput::make('documents'),
                TextInput::make('faqs'),
            ]);
    }
}
