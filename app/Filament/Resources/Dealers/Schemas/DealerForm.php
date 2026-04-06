<?php

namespace App\Filament\Resources\Dealers\Schemas;

use Filament\Schemas\Schema;

class DealerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('Thông tin định danh & Tài khoản')
                    ->description('Mật khẩu dùng cho ứng dụng di động / API sau này.')
                    ->icon('heroicon-o-users')
                    ->schema([
                        \Filament\Forms\Components\FileUpload::make('avatar')
                            ->label('Ảnh đại diện')
                            ->image()
                            ->avatar()
                            ->directory('dealers/avatars')
                            ->columnSpanFull(),
                        \Filament\Forms\Components\TextInput::make('name')
                            ->label('Tên đại lý')
                            ->prefixIcon('heroicon-m-building-office-2')
                            ->required()
                            ->maxLength(255),
                        \Filament\Forms\Components\TextInput::make('code')
                            ->label('Mã đại lý')
                            ->prefixIcon('heroicon-m-qr-code')
                            ->required()
                            ->maxLength(255),
                        \Filament\Forms\Components\TextInput::make('phone')
                            ->label('Số điện thoại')
                            ->prefixIcon('heroicon-m-phone')
                            ->tel()
                            ->required()
                            ->maxLength(255),
                        \Filament\Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->prefixIcon('heroicon-m-envelope')
                            ->email()
                            ->maxLength(255),
                        \Filament\Forms\Components\TextInput::make('password')
                            ->label('Mật khẩu đăng nhập (API/App)')
                            ->prefixIcon('heroicon-m-lock-closed')
                            ->password()
                            ->revealable()
                            ->dehydrated(fn($state) => filled($state))
                            ->required(fn(string $context): bool => $context === 'create')
                            ->columnSpanFull(),
                    ])->columns(2),

                \Filament\Schemas\Components\Section::make('Quản lý trạng thái')
                    ->description('Thiết lập mức độ ưu tiên và trạng thái hợp tác.')
                    ->icon('heroicon-o-cog-8-tooth')
                    ->schema([
                        \Filament\Forms\Components\Select::make('status')
                            ->label('Trạng thái')
                            ->options([
                                'draft' => 'Nháp',
                                'pending' => 'Chờ duyệt',
                                'approved' => 'Đã duyệt',
                                'inactive' => 'Ngưng hoạt động',
                            ])
                            ->default('pending')
                            ->required(),
                        \Filament\Forms\Components\TextInput::make('priority_order')
                            ->label('Thứ tự ưu tiên')
                            ->prefixIcon('heroicon-m-arrows-up-down')
                            ->numeric()
                            ->default(0),
                    ])->columns(2),

                \Filament\Schemas\Components\Section::make('Vị trí & Vùng phủ')
                    ->description('Thông tin địa bàn hoạt động.')
                    ->icon('heroicon-o-map')
                    ->schema([
                        \Filament\Forms\Components\Select::make('province_id')
                            ->label('Tỉnh / Thành phố')
                            ->prefixIcon('heroicon-m-map')
                            ->options(\App\Models\Province::whereNull('parent_id')->pluck('name', 'id'))
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(fn($set) => $set('district_id', null))
                            ->required(),
                        \Filament\Forms\Components\Select::make('district_id')
                            ->label('Quận / Huyện')
                            ->prefixIcon('heroicon-m-map-pin')
                            ->options(fn($get) => \App\Models\Province::where('parent_id', $get('province_id'))->pluck('name', 'id'))
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(fn($set) => $set('ward_id', null))
                            ->required(),
                        \Filament\Forms\Components\Select::make('ward_id')
                            ->label('Phường / Xã')
                            ->prefixIcon('heroicon-m-hashtag')
                            ->options(fn($get) => \App\Models\Province::where('parent_id', $get('district_id'))->pluck('name', 'id'))
                            ->searchable()
                            ->required(),

                        \Filament\Forms\Components\Textarea::make('address')
                            ->label('Địa chỉ chi tiết')
                            ->rows(3),

                        \Filament\Forms\Components\Select::make('coverage_area')
                            ->label('Vùng phủ sóng')
                            ->prefixIcon('heroicon-m-globe-alt')
                            ->multiple()
                            ->options(\App\Models\Province::whereNull('parent_id')->pluck('name', 'id'))
                            ->searchable()
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }
}
