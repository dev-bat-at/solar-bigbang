<?php

namespace App\Filament\Resources\AdminUsers\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AdminUserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('Thông tin tài khoản')
                    ->description('Thông tin đăng nhập và xác thực cơ bản của nhân viên.')
                    ->icon('heroicon-o-user-circle')
                    ->schema([
                        TextInput::make('name')
                            ->label('Họ tên')
                            ->prefixIcon('heroicon-m-user')
                            ->placeholder('Ví dụ: Nguyễn Văn A')
                            ->required(),
                        TextInput::make('email')
                            ->label('Email')
                            ->prefixIcon('heroicon-m-envelope')
                            ->email()
                            ->unique(ignoreRecord: true)
                            ->required(),
                        TextInput::make('password')
                            ->label('Mật khẩu')
                            ->prefixIcon('heroicon-m-lock-closed')
                            ->password()
                            ->dehydrated(fn($state) => filled($state))
                            ->required(fn(string $context): bool => $context === 'create'),
                        DateTimePicker::make('email_verified_at')
                            ->label('Ngày xác thực email')
                            ->prefixIcon('heroicon-m-check-badge'),

                        \Filament\Forms\Components\FileUpload::make('avatar_url')
                            ->label('Ảnh đại diện')
                            ->image()
                            ->disk('root_public')
                            ->directory('avatars')
                            ->avatar()
                            ->columnSpanFull()
                            ->alignCenter(),
                    ])->columns(2),

                \Filament\Schemas\Components\Section::make('Phân quyền & Khu vực')
                    ->description('Thiết lập quyền hạn và phạm vi hoạt động của nhân sự.')
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        Select::make('roles')
                            ->label('Vai trò')
                            ->prefixIcon('heroicon-m-key')
                            ->relationship('roles', 'name')
                            ->preload()
                            ->multiple()
                            ->helperText('SUPER_ADMIN: Full quyền, OFFICE_ADMIN: Quản lý văn phòng...')
                            ->required(),
                        Select::make('covered_areas')
                            ->label('Khu vực phụ trách')
                            ->prefixIcon('heroicon-m-map-pin')
                            ->multiple()
                            ->options(\App\Models\Province::whereNull('parent_id')->pluck('name', 'id'))
                            ->searchable()
                            ->dehydrated(fn (): bool => \Illuminate\Support\Facades\Schema::hasColumn('admin_users', 'covered_areas'))
                            ->helperText('Chỉ áp dụng cho các nhân sự trực thuộc khu vực cụ thể.'),
                    ])->columns(2),

                \Filament\Schemas\Components\Section::make('Bảo mật & Trạng thái tài khoản')
                    ->description('Quản lý trạng thái hoạt động và các yêu cầu bảo mật.')
                    ->icon('heroicon-o-lock-closed')
                    ->collapsed() // Gập lại cho gọn nếu cần, hoặc để mở
                    ->schema([
                        Select::make('status')
                            ->label('Trạng thái tài khoản')
                            ->options([
                                'active' => 'Hoạt động',
                                'inactive' => 'Ngưng hoạt động',
                                'locked' => 'Bị khóa',
                            ])
                            ->required()
                            ->default('active'),
                        Toggle::make('force_change_password')
                            ->label('Yêu cầu đổi mật khẩu ở lần đăng nhập tới')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }
}
