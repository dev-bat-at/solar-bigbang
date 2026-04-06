<?php

namespace App\Filament\Resources\Dealers;

use App\Filament\Resources\Dealers\Pages\CreateDealer;
use App\Filament\Resources\Dealers\Pages\EditDealer;
use App\Filament\Resources\Dealers\Pages\ListDealers;
use App\Filament\Resources\Dealers\Schemas\DealerForm;
use App\Filament\Resources\Dealers\Tables\DealersTable;
use App\Models\Dealer;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DealerResource extends Resource
{
    protected static ?string $model = Dealer::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-building-storefront';

    protected static string | \UnitEnum | null $navigationGroup = 'Quản lý Đối tác & Lead';

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return 'Đại lý';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Đại lý';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return ! in_array(static::class, config('admin_menu.hidden_resources', []));
    }

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return DealerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DealersTable::configure($table);
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
            'index' => ListDealers::route('/'),
            'create' => CreateDealer::route('/create'),
            'edit' => EditDealer::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
