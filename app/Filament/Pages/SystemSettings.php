<?php

namespace App\Filament\Pages;

use App\Models\SystemSetting;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;

class SystemSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Cấu hình hệ thống';

    protected static ?string $title = 'Cấu hình hệ thống';

    protected static string|\UnitEnum|null $navigationGroup = 'Cấu hình';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.system-settings';

    public ?array $data = [];

    public static function shouldRegisterNavigation(): bool
    {
        return ! in_array(static::class, config('admin_menu.hidden_resources', []), true);
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return $user->hasRole('Super Admin') || $user->can('settings.view');
    }

    public function mount(): void
    {
        $this->data = [
            'app_name' => SystemSetting::get('app_name', 'Solar BigBang'),
            'app_short_name' => SystemSetting::get('app_short_name', 'SBB'),
            'login_title' => SystemSetting::get('login_title', 'Đăng nhập hệ thống'),
            'login_subtitle' => SystemSetting::get('login_subtitle', 'Hệ thống quản lý Solar BigBang'),
            'app_logo' => SystemSetting::get('app_logo'),
            'login_background_image' => SystemSetting::get('login_background_image'),
            'contact_phone' => SystemSetting::get('contact_phone'),
            'contact_zalo_link' => SystemSetting::get('contact_zalo_link'),
            'contact_email' => SystemSetting::get('contact_email'),
            'contact_business_hours' => SystemSetting::get('contact_business_hours'),
            'timezone' => SystemSetting::get('timezone', 'Asia/Ho_Chi_Minh'),
            'locale' => SystemSetting::get('locale', 'vi'),
            'date_format' => SystemSetting::get('date_format', 'd/m/Y'),
            'datetime_format' => SystemSetting::get('datetime_format', 'd/m/Y H:i'),
            'upload_disk' => SystemSetting::get('upload_disk', 'public'),
        ];

        $this->form->fill($this->data);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Tabs::make('settings')
                    ->tabs([
                        Tabs\Tab::make('Thông tin ứng dụng')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Forms\Components\TextInput::make('app_name')
                                    ->label('Tên ứng dụng')
                                    ->required(),
                                Forms\Components\TextInput::make('app_short_name')
                                    ->label('Tên viết tắt'),
                                Forms\Components\TextInput::make('login_title')
                                    ->label('Tiêu đề trang đăng nhập'),
                                Forms\Components\TextInput::make('login_subtitle')
                                    ->label('Phụ đề trang đăng nhập'),
                                Forms\Components\FileUpload::make('app_logo')
                                    ->label('Logo ứng dụng')
                                    ->image()
                                    ->directory('settings')
                                    ->disk('root_public')
                                    ->imageResizeMode('cover')
                                    ->maxSize(2048),
                                Forms\Components\FileUpload::make('login_background_image')
                                    ->label('Ảnh nền trang đăng nhập')
                                    ->image()
                                    ->directory('settings')
                                    ->disk('root_public')
                                    ->imageResizeMode('cover')
                                    ->maxSize(5120),
                            ]),
                        Tabs\Tab::make('Cấu hình hiển thị')
                            ->icon('heroicon-o-eye')
                            ->schema([
                                Forms\Components\Select::make('timezone')
                                    ->label('Múi giờ')
                                    ->options([
                                        'Asia/Ho_Chi_Minh' => 'Asia/Ho_Chi_Minh (UTC+7)',
                                        'UTC' => 'UTC',
                                    ])
                                    ->default('Asia/Ho_Chi_Minh'),
                                Forms\Components\Select::make('locale')
                                    ->label('Ngôn ngữ')
                                    ->options([
                                        'vi' => 'Tiếng Việt',
                                        'en' => 'English',
                                    ])
                                    ->default('vi'),
                                Forms\Components\TextInput::make('date_format')
                                    ->label('Định dạng ngày')
                                    ->default('d/m/Y'),
                                Forms\Components\TextInput::make('datetime_format')
                                    ->label('Định dạng ngày giờ')
                                    ->default('d/m/Y H:i'),
                            ]),
                        Tabs\Tab::make('Liên hệ')
                            ->icon('heroicon-o-phone')
                            ->schema([
                                Forms\Components\TextInput::make('contact_phone')
                                    ->label('Số điện thoại')
                                    ->tel()
                                    ->maxLength(255)
                                    ->placeholder('VD: 0901234567'),
                                Forms\Components\TextInput::make('contact_zalo_link')
                                    ->label('Link Zalo')
                                    ->url()
                                    ->maxLength(500)
                                    ->placeholder('VD: https://zalo.me/0901234567'),
                                Forms\Components\TextInput::make('contact_email')
                                    ->label('Email liên hệ')
                                    ->email()
                                    ->maxLength(255)
                                    ->placeholder('VD: lienhe@solarbigbang.vn'),
                                Forms\Components\TextInput::make('contact_business_hours')
                                    ->label('Giờ làm việc')
                                    ->maxLength(255)
                                    ->placeholder('VD: 08:00 - 17:30 - Thứ 2 đến Thứ 7'),
                            ]),
                        Tabs\Tab::make('Kỹ thuật')
                            ->icon('heroicon-o-wrench')
                            ->schema([
                                Forms\Components\Select::make('upload_disk')
                                    ->label('Disk upload mặc định')
                                    ->options([
                                        'root_public' => 'Thư mục public (Trực tiếp)',
                                        'public' => 'Storage (Symlinked)',
                                        's3' => 'Amazon S3',
                                    ])
                                    ->default('root_public'),
                            ]),
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            $group = match (true) {
                in_array($key, ['app_name', 'app_short_name', 'login_title', 'login_subtitle', 'app_logo', 'login_background_image'], true) => 'branding',
                in_array($key, ['contact_phone', 'contact_zalo_link', 'contact_email', 'contact_business_hours'], true) => 'contact',
                in_array($key, ['timezone', 'locale', 'date_format', 'datetime_format'], true) => 'display',
                default => 'technical',
            };

            SystemSetting::set($key, $value, $group);
        }

        SystemSetting::clearCache();

        activity('settings')
            ->causedBy(auth()->user())
            ->withProperties($data)
            ->log('Cập nhật cấu hình hệ thống');

        Notification::make()
            ->title('Đã lưu cấu hình thành công')
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Lưu cấu hình')
                ->submit('submit'),
        ];
    }
}
