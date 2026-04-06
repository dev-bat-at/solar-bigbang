<?php

namespace App\Filament\Resources\Provinces;

use App\Filament\Resources\Provinces\Pages\CreateProvince;
use App\Filament\Resources\Provinces\Pages\EditProvince;
use App\Filament\Resources\Provinces\Pages\ListProvinces;
use App\Filament\Resources\Provinces\Schemas\ProvinceForm;
use App\Filament\Resources\Provinces\Tables\ProvincesTable;
use App\Models\Province;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProvinceResource extends Resource
{
    protected static ?string $model = Province::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static string | \UnitEnum | null $navigationGroup = 'Cấu hình';

    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        return ! in_array(static::class, config('admin_menu.hidden_resources', []));
    }

    public static function getModelLabel(): string
    {
        return 'Tỉnh / Thành';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Tỉnh / Thành phố';
    }

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return ProvinceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProvincesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProvinces::route('/'),
            'edit' => EditProvince::route('/{record}/edit'),
        ];
    }
}
