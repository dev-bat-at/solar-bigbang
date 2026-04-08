<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationLabel = 'Người dùng';

    protected static ?string $modelLabel = 'Người dùng';

    protected static ?string $pluralModelLabel = 'Người dùng';

    protected static string|\UnitEnum|null $navigationGroup = 'Quản lý Đối tác & Lead';

    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool
    {
        return !in_array(static::class, config('admin_menu.hidden_resources', []));
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Thông tin người dùng')
                    ->description('Quản lý tài khoản người dùng đăng ký trực tiếp trên hệ thống.')
                    ->icon('heroicon-o-user')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')
                            ->label('Họ và tên')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->label('Số điện thoại')
                            ->tel()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Select::make('province_id')
                            ->label('Tỉnh/Thành phố')
                            ->relationship('province', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('Chọn tỉnh/thành phố'),
                    ])->columns(2),

                Section::make('Bảo mật')
                    ->description('Thiết lập mật khẩu và trạng thái xác thực email.')
                    ->icon('heroicon-o-lock-closed')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('password')
                            ->label('Mật khẩu')
                            ->password()
                            ->required(fn(string $operation): bool => $operation === 'create')
                            ->dehydrated(fn($state): bool => filled($state)),
                        DateTimePicker::make('email_verified_at')
                            ->label('Ngày xác thực email')
                            ->seconds(false),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Họ và tên')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone')
                    ->label('Số điện thoại')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('province.name')
                    ->label('Tỉnh/Thành')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),
                // IconColumn::make('email_verified_at')
                //     ->label('Xác thực email')
                //     ->boolean()
                //     ->getStateUsing(fn (User $record): bool => filled($record->email_verified_at)),
                TextColumn::make('created_at')
                    ->label('Ngày đăng ký')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('province_id')
                    ->label('Tỉnh/Thành')
                    ->relationship('province', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('Tất cả tỉnh/thành'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
