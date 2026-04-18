<?php

namespace App\Filament\Resources\ProductCategories;

use App\Filament\Resources\ProductCategories\Pages\ListProductCategories;
use App\Models\ProductCategory;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ProductCategoryResource extends Resource
{
    protected static ?string $model = ProductCategory::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Loại sản phẩm';

    protected static ?string $modelLabel = 'Loại sản phẩm';

    protected static ?string $pluralModelLabel = 'Loại sản phẩm';

    protected static string|\UnitEnum|null $navigationGroup = 'Nội dung & Sản phẩm';

    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        return ! in_array(static::class, config('admin_menu.hidden_resources', []), true);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Thông tin loại sản phẩm')
                    ->description('Tạo loại cha hoặc danh mục con cho sản phẩm.')
                    ->icon('heroicon-o-tag')
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\Select::make('parent_id')
                            ->label('Loại cha')
                            ->relationship('parent', 'name_vi', fn ($query) => $query->whereNull('parent_id'))
                            ->searchable()
                            ->preload()
                            ->placeholder('Không có - đây là loại cha'),
                        Forms\Components\TextInput::make('name_vi')
                            ->label('Tên loại (Tiếng Việt)')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (string $operation, $state, $set) => $operation === 'create' ? $set('slug', Str::slug($state)) : null),
                        Forms\Components\TextInput::make('name_en')
                            ->label('Tên loại (Tiếng Anh)')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ProductCategory::class, 'slug', ignoreRecord: true),
                        Forms\Components\TextInput::make('sort_order')
                            ->label('Thứ tự')
                            ->numeric()
                            ->default(0),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Hiển thị')
                            ->default(true),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Tên loại')
                    ->formatStateUsing(fn ($state, $record): string => $record->name_vi ?: $state)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name_en')
                    ->label('Tên EN')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('parent.name')
                    ->label('Thuộc loại')
                    ->placeholder('Loại cha')
                    ->formatStateUsing(fn ($state, $record): string => $record->parent?->name_vi ?: $state)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('children_count')
                    ->label('Mục con')
                    ->counts('children')
                    ->badge(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Hiển thị')
                    ->boolean(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Thứ tự')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Ngày tạo')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('parent_id')
                    ->label('Loại cha')
                    ->relationship('parent', 'name_vi')
                    ->searchable()
                    ->preload()
                    ->placeholder('Tất cả'),
            ])
            ->actions([
                EditAction::make()
                    ->modalWidth(\Filament\Support\Enums\Width::FourExtraLarge),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductCategories::route('/'),
        ];
    }
}
