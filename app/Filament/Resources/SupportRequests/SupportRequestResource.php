<?php

namespace App\Filament\Resources\SupportRequests;

use App\Filament\Resources\SupportRequests\Pages\CreateSupportRequest;
use App\Filament\Resources\SupportRequests\Pages\EditSupportRequest;
use App\Filament\Resources\SupportRequests\Pages\ListSupportRequests;
use App\Models\SupportRequest;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SupportRequestResource extends Resource
{
    protected static ?string $model = SupportRequest::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'Liên hệ & Báo giá';

    protected static ?string $modelLabel = 'Yêu cầu khách hàng';

    protected static ?string $pluralModelLabel = 'Liên hệ & Báo giá';

    protected static string|\UnitEnum|null $navigationGroup = 'Quản lý Đối tác & Lead';

    protected static ?int $navigationSort = 3;

    public static function shouldRegisterNavigation(): bool
    {
        return ! in_array(static::class, config('admin_menu.hidden_resources', []));
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Thông tin khách hàng')
                    ->description('Lưu form liên hệ của khách gửi trực tiếp cho admin.')
                    ->icon('heroicon-o-user')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('customer_name')
                            ->label('Họ và tên')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('customer_phone')
                            ->label('Số điện thoại')
                            ->tel()
                            ->required()
                            ->maxLength(255),
                        TextInput::make('customer_email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255),
                        Textarea::make('customer_address')
                            ->label('Địa chỉ')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('Nội dung yêu cầu')
                    ->description('Phân loại rõ yêu cầu liên hệ, báo giá theo sản phẩm hoặc theo hệ.')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->columnSpanFull()
                    ->schema([
                        Select::make('request_type')
                            ->label('Loại yêu cầu')
                            ->options(SupportRequest::requestTypeOptions())
                            ->required()
                            ->default('general_contact')
                            ->live()
                            ->native(false),
                        Select::make('product_id')
                            ->label('Sản phẩm cần báo giá')
                            ->relationship('product', 'name_vi')
                            ->searchable()
                            ->preload()
                            ->visible(fn ($get) => $get('request_type') === 'product_quote')
                            ->required(fn ($get) => $get('request_type') === 'product_quote'),
                        Select::make('system_type_id')
                            ->label('Hệ cần báo giá')
                            ->relationship('systemType', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(fn ($get) => $get('request_type') === 'system_quote')
                            ->required(fn ($get) => $get('request_type') === 'system_quote'),
                        Textarea::make('customer_message')
                            ->label('Nội dung khách gửi')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('Xử lý nội bộ')
                    ->description('Theo dõi trạng thái tiếp nhận và ghi chú xử lý của admin.')
                    ->icon('heroicon-o-wrench-screwdriver')
                    ->columnSpanFull()
                    ->schema([
                        Select::make('status')
                            ->label('Trạng thái')
                            ->options(SupportRequest::statusOptions())
                            ->required()
                            ->default('new')
                            ->native(false),
                        Select::make('source')
                            ->label('Nguồn gửi')
                            ->options(SupportRequest::sourceOptions())
                            ->required()
                            ->default('admin_manual')
                            ->native(false),
                        DateTimePicker::make('handled_at')
                            ->label('Thời điểm xử lý')
                            ->seconds(false),
                        Textarea::make('admin_note')
                            ->label('Ghi chú nội bộ')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer_name')
                    ->label('Khách hàng')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer_phone')
                    ->label('Điện thoại')
                    ->searchable(),
                TextColumn::make('customer_email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('request_type')
                    ->label('Loại yêu cầu')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => SupportRequest::requestTypeOptions()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'product_quote' => 'warning',
                        'system_quote' => 'success',
                        default => 'info',
                    }),
                TextColumn::make('target_label')
                    ->label('Đối tượng')
                    ->placeholder('Liên hệ chung')
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => SupportRequest::statusOptions()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'new' => 'danger',
                        'contacted' => 'warning',
                        'quoted' => 'success',
                        'resolved' => 'info',
                        'cancelled' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('source')
                    ->label('Nguồn')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => SupportRequest::sourceOptions()[$state] ?? $state)
                    ->color('gray')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Ngày tạo')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('request_type')
                    ->label('Loại yêu cầu')
                    ->options(SupportRequest::requestTypeOptions()),
                SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options(SupportRequest::statusOptions()),
                SelectFilter::make('source')
                    ->label('Nguồn gửi')
                    ->options(SupportRequest::sourceOptions()),
                TrashedFilter::make(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSupportRequests::route('/'),
            'create' => CreateSupportRequest::route('/create'),
            'edit' => EditSupportRequest::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
