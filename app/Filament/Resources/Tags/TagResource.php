<?php

namespace App\Filament\Resources\Tags;

use App\Filament\Resources\Tags\Pages\ListTags;
use App\Models\Tag;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class TagResource extends Resource
{
    protected static ?string $model = Tag::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static string|\UnitEnum|null $navigationGroup = 'Quản lý Nội dung';

    protected static ?string $modelLabel = 'Tag bài viết';

    protected static ?string $pluralModelLabel = 'Quản lý Tag';

    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool
    {
        return ! in_array(static::class, config('admin_menu.hidden_resources', []));
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Thông tin Tag')
                    ->description('Phân loại các bài viết tin tức.')
                    ->icon('heroicon-o-tag')
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\TextInput::make('name_vi')
                            ->label('Tên Tag (Tiếng Việt)')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (string $operation, $state, $set) => $operation === 'create' ? $set('slug', Str::slug($state)) : null),
                        Forms\Components\TextInput::make('name_en')
                            ->label('Tên Tag (Tiếng Anh)')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(Tag::class, 'slug', ignoreRecord: true),
                        Forms\Components\ColorPicker::make('color')
                            ->label('Màu tag')
                            ->hex()
                            ->helperText('API sẽ trả theo định dạng 0xFF + mã màu, ví dụ 0xFFFF6B00.')
                            ->rule('nullable')
                            ->rule('regex:/^#?[0-9A-Fa-f]{6}$/'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Tên Tag')
                    ->formatStateUsing(fn ($state, $record): string => $record->name_vi ?: $state)
                    ->searchable(),
                Tables\Columns\TextColumn::make('name_en')
                    ->label('Tên EN')
                    ->searchable(),
                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable(),
                Tables\Columns\ColorColumn::make('color')
                    ->label('Màu')
                    ->copyable()
                    ->placeholder('Chưa chọn'),
                Tables\Columns\TextColumn::make('posts_count')
                    ->label('Số bài viết')
                    ->counts('posts'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Ngày tạo')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make()
                    ->modalWidth(Width::FourExtraLarge),
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
            'index' => ListTags::route('/'),
        ];
    }
}
