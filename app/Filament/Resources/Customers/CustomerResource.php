<?php

namespace App\Filament\Resources\Customers;

use App\Filament\Resources\Customers\Pages\CreateCustomer;
use App\Filament\Resources\Customers\Pages\EditCustomer;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Models\Customer;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Khách hàng đại lý';

    protected static ?string $modelLabel = 'Khách hàng đại lý';

    protected static ?string $pluralModelLabel = 'Khách hàng đại lý';

    protected static string|\UnitEnum|null $navigationGroup = 'Quản lý Đối tác & Lead';

    protected static ?int $navigationSort = 5;

    public static function shouldRegisterNavigation(): bool
    {
        return !in_array(static::class, config('admin_menu.hidden_resources', []));
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Đại lý quản lý')
                    ->description('Đây là khách hàng phát sinh từ luồng liên hệ với đại lý, không phải người dùng đăng ký tài khoản.')
                    ->icon('heroicon-o-building-storefront')
                    ->columnSpanFull()
                    ->schema([
                        Select::make('dealer_id')
                            ->label('Đại lý')
                            ->prefixIcon('heroicon-m-building-storefront')
                            ->relationship('dealer', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->placeholder('Chọn đại lý')
                            ->helperText('Khách hàng này đang thuộc đại lý nào?'),
                        Select::make('system_type_id')
                            ->label('Hệ thống quan tâm')
                            ->prefixIcon('heroicon-m-bolt')
                            ->relationship('systemType', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('Chọn hệ thống'),
                        TextInput::make('contact_time')
                            ->label('Thời gian ưu tiên liên hệ')
                            ->prefixIcon('heroicon-m-clock')
                            ->maxLength(255),
                    ])->columns(2),

                Section::make('Thông tin khách hàng đại lý')
                    ->description('Lưu thông tin liên hệ của khách hàng được đại lý chăm sóc.')
                    ->icon('heroicon-o-user-group')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')
                            ->label('Họ và tên')
                            ->prefixIcon('heroicon-m-user')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->label('Số điện thoại')
                            ->prefixIcon('heroicon-m-phone')
                            ->tel()
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Địa chỉ email')
                            ->prefixIcon('heroicon-m-envelope')
                            ->email()
                            ->maxLength(255),
                        Textarea::make('address')
                            ->label('Địa chỉ')
                            ->rows(2)
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('Trạng thái tài khoản')
                    ->description('Quản lý trạng thái hoạt động của khách hàng trong luồng đại lý.')
                    ->icon('heroicon-o-shield-check')
                    ->columnSpanFull()
                    ->schema([
                        Select::make('status')
                            ->label('Trạng thái')
                            ->prefixIcon('heroicon-m-check-circle')
                            ->options([
                                'active' => 'Hoạt động',
                                'inactive' => 'Ngưng hoạt động',
                                'locked' => 'Bị khóa',
                            ])
                            ->required()
                            ->default('active')
                            ->live(),
                        TextInput::make('lock_reason')
                            ->label('Lý do khóa')
                            ->prefixIcon('heroicon-m-lock-closed')
                            ->maxLength(255)
                            ->visible(fn($get) => $get('status') === 'locked')
                            ->requiredIf('status', 'locked')
                            ->helperText('Bắt buộc nhập lý do khi khóa tài khoản.'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('dealer.name')
                    ->label('Đại lý')
                    ->icon('heroicon-m-building-storefront')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),
                TextColumn::make('name')
                    ->label('Họ và tên')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone')
                    ->label('Số điện thoại')
                    ->searchable()
                    ->icon('heroicon-m-phone'),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->icon('heroicon-m-envelope')
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('systemType.name')
                    ->label('Hệ thống quan tâm')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('success')
                    ->placeholder('—'),
                TextColumn::make('contact_time')
                    ->label('Giờ liên hệ')
                    ->searchable()
                    ->limit(20)
                    ->placeholder('—'),
                TextColumn::make('address')
                    ->label('Địa chỉ')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'active' => 'Hoạt động',
                        'inactive' => 'Ngưng hoạt động',
                        'locked' => 'Bị khóa',
                        default => $state,
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'gray',
                        'locked' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('lock_reason')
                    ->label('Lý do khóa')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('—'),
                // TextColumn::make('leads_count')
                //     ->label('Số lead')
                //     ->counts('leads')
                //     ->sortable()
                //     ->badge()
                //     ->color('warning'),
                TextColumn::make('created_at')
                    ->label('Ngày tạo')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('dealer_id')
                    ->label('Đại lý')
                    ->relationship('dealer', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('Tất cả đại lý'),
                SelectFilter::make('system_type_id')
                    ->label('Hệ thống quan tâm')
                    ->relationship('systemType', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('Tất cả hệ thống'),
                SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options([
                        'active' => 'Hoạt động',
                        'inactive' => 'Ngưng hoạt động',
                        'locked' => 'Bị khóa',
                    ]),
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
            'index' => ListCustomers::route('/'),
            'create' => CreateCustomer::route('/create'),
            'edit' => EditCustomer::route('/{record}/edit'),
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
