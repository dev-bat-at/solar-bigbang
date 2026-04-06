<?php

namespace App\Filament\Resources\SystemTypes;

use App\Filament\Resources\SystemTypes\Pages\ManageSystemTypes;
use App\Models\SystemType;
use BackedEnum;
use Filament\Forms;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class SystemTypeResource extends Resource
{
    protected static ?string $model = SystemType::class;

    protected static ?string $navigationLabel = 'Hệ';

    protected static ?string $modelLabel = 'Hệ';

    protected static ?string $pluralModelLabel = 'Hệ';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Tabs::make('system_type_tabs')
                    ->tabs([
                        \Filament\Schemas\Components\Tabs\Tab::make('Thông tin hệ')
                            ->icon('heroicon-o-rectangle-stack')
                            ->schema([
                                Section::make('Thông tin hệ')
                                    ->description('Quản lý hệ và kích hoạt cấu hình báo giá cho frontend.')
                                    ->columnSpanFull()
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Tên hệ')
                                            ->required()
                                            ->maxLength(255)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn (string $operation, $state, $set) => $operation === 'create' ? $set('slug', Str::slug($state)) : null),
                                        Forms\Components\TextInput::make('slug')
                                            ->label('Slug')
                                            ->required()
                                            ->maxLength(255)
                                            ->unique(SystemType::class, 'slug', ignoreRecord: true),
                                        Forms\Components\Textarea::make('description')
                                            ->label('Mô tả')
                                            ->rows(3)
                                            ->columnSpanFull(),
                                        Forms\Components\Select::make('quote_formula_type')
                                            ->label('Loại công thức báo giá')
                                            ->options([
                                                'bam_tai' => 'Bám tải',
                                                'hybrid' => 'Hybrid',
                                            ])
                                            ->helperText('Chọn đúng loại hệ để frontend dùng công thức phù hợp.')
                                            ->live()
                                            ->columnSpan(1),
                                        Forms\Components\Toggle::make('quote_is_active')
                                            ->label('Cho phép báo giá')
                                            ->helperText('Bật để hệ này xuất hiện và cho phép tính báo giá.')
                                            ->inline(false)
                                            ->default(false)
                                            ->columnSpan(1),
                                    ])
                                    ->columns(2),
                            ]),

                        \Filament\Schemas\Components\Tabs\Tab::make('Tham số tính')
                            ->icon('heroicon-o-calculator')
                            ->schema([
                                Section::make('Hằng số & tham số công thức')
                                    ->description('Các giá trị này được dùng để tính công suất đề xuất, chi phí đầu tư và tiết kiệm/tháng.')
                                    ->columnSpanFull()
                                    ->schema([
                                        Forms\Components\TextInput::make('quote_settings.electric_price')
                                            ->label('Giá điện quy đổi (VNĐ/kWh)')
                                            ->numeric()
                                            ->default(2500)
                                            ->required(),
                                        Forms\Components\TextInput::make('quote_settings.yield')
                                            ->label('Sản lượng quy đổi / kWp')
                                            ->numeric()
                                            ->default(120)
                                            ->required(),
                                        Forms\Components\TextInput::make('quote_settings.market_factor')
                                            ->label('Hệ số thị trường')
                                            ->numeric()
                                            ->default(1)
                                            ->step('0.01')
                                            ->required(),
                                        Forms\Components\TextInput::make('quote_settings.saving_factor')
                                            ->label('Hệ số tiết kiệm/tháng')
                                            ->numeric()
                                            ->default(1)
                                            ->step('0.01')
                                            ->helperText('Cho phép hiệu chỉnh số tiết kiệm ước tính.'),
                                        Forms\Components\TextInput::make('quote_settings.k_factor')
                                            ->label('K factor')
                                            ->numeric()
                                            ->default(1)
                                            ->step('0.01')
                                            ->visible(fn ($get) => $get('quote_formula_type') === 'bam_tai'),
                                        Forms\Components\TextInput::make('quote_settings.day_ratio_default')
                                            ->label('Tỉ lệ ngày mặc định')
                                            ->numeric()
                                            ->default(0.5)
                                            ->step('0.01')
                                            ->helperText('Nhập dạng 0.5 hoặc 50.')
                                            ->visible(fn ($get) => in_array($get('quote_formula_type'), ['bam_tai', 'hybrid'], true)),
                                        Forms\Components\TextInput::make('quote_settings.battery_price_per_kwh')
                                            ->label('Giá pin lưu trữ / kWh')
                                            ->numeric()
                                            ->default(2500000)
                                            ->visible(fn ($get) => $get('quote_formula_type') === 'hybrid'),
                                        Forms\Components\TextInput::make('quote_settings.backup_hours')
                                            ->label('Số giờ backup')
                                            ->numeric()
                                            ->default(1)
                                            ->step('0.1')
                                            ->visible(fn ($get) => $get('quote_formula_type') === 'hybrid'),
                                    ])
                                    ->columns(2),
                            ]),

                        \Filament\Schemas\Components\Tabs\Tab::make('Đơn giá theo mốc')
                            ->icon('heroicon-o-banknotes')
                            ->schema([
                                Section::make('Mốc giá / kWp')
                                    ->description('Khi hệ tính ra kWp đề xuất, hệ thống sẽ dò bảng này để lấy đơn giá phù hợp.')
                                    ->columnSpanFull()
                                    ->schema([
                                        Forms\Components\Repeater::make('quote_price_tiers')
                                            ->label('Bảng giá')
                                            ->defaultItems(0)
                                            ->reorderable()
                                            ->cloneable()
                                            ->collapsed()
                                            ->addActionLabel('Thêm mốc giá')
                                            ->schema([
                                                Forms\Components\Select::make('phase_type')
                                                    ->label('Loại điện')
                                                    ->options([
                                                        'ALL' => 'Dùng chung',
                                                        '1P' => '1 pha',
                                                        '3P' => '3 pha',
                                                    ])
                                                    ->default('ALL')
                                                    ->required(),
                                                Forms\Components\TextInput::make('min_kw')
                                                    ->label('Từ kWp')
                                                    ->numeric()
                                                    ->required(),
                                                Forms\Components\TextInput::make('max_kw')
                                                    ->label('Đến kWp')
                                                    ->numeric()
                                                    ->helperText('Để trống nếu là mức cuối cùng.'),
                                                Forms\Components\TextInput::make('price_per_kw')
                                                    ->label('Đơn giá / kWp')
                                                    ->numeric()
                                                    ->required(),
                                            ])
                                            ->columns(4)
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        \Filament\Schemas\Components\Tabs\Tab::make('Cấu hình gợi ý')
                            ->icon('heroicon-o-light-bulb')
                            ->schema([
                                Section::make('Thiết bị gợi ý theo mốc')
                                    ->description('Frontend dùng dữ liệu này để hiển thị số tấm pin, inverter và pin lưu trữ gợi ý.')
                                    ->columnSpanFull()
                                    ->schema([
                                        Forms\Components\Repeater::make('quote_recommendations')
                                            ->label('Bảng cấu hình gợi ý')
                                            ->defaultItems(0)
                                            ->reorderable()
                                            ->cloneable()
                                            ->collapsed()
                                            ->addActionLabel('Thêm cấu hình gợi ý')
                                            ->schema([
                                                Forms\Components\Select::make('phase_type')
                                                    ->label('Loại điện')
                                                    ->options([
                                                        'ALL' => 'Dùng chung',
                                                        '1P' => '1 pha',
                                                        '3P' => '3 pha',
                                                    ])
                                                    ->default('ALL')
                                                    ->required(),
                                                Forms\Components\TextInput::make('min_kw')
                                                    ->label('Từ kWp')
                                                    ->numeric()
                                                    ->required(),
                                                Forms\Components\TextInput::make('max_kw')
                                                    ->label('Đến kWp')
                                                    ->numeric(),
                                                Forms\Components\TextInput::make('panel_model')
                                                    ->label('Model tấm pin'),
                                                Forms\Components\TextInput::make('panel_watt')
                                                    ->label('Watt / tấm')
                                                    ->numeric(),
                                                Forms\Components\TextInput::make('panel_count')
                                                    ->label('Số tấm cố định')
                                                    ->numeric()
                                                    ->helperText('Để trống để hệ thống tự tính từ kWp và Watt/tấm.'),
                                                Forms\Components\TextInput::make('inverter_model')
                                                    ->label('Model inverter'),
                                                Forms\Components\TextInput::make('inverter_kw')
                                                    ->label('Công suất inverter')
                                                    ->numeric(),
                                                Forms\Components\TextInput::make('battery_model')
                                                    ->label('Model pin lưu trữ'),
                                                Forms\Components\TextInput::make('battery_kwh')
                                                    ->label('Dung lượng pin')
                                                    ->numeric(),
                                                Forms\Components\Textarea::make('note')
                                                    ->label('Ghi chú')
                                                    ->rows(2)
                                                    ->columnSpanFull(),
                                            ])
                                            ->columns(3)
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Tên hệ')
                    ->searchable(),
                Tables\Columns\TextColumn::make('quote_formula_type')
                    ->label('Công thức')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'bam_tai' => 'Bám tải',
                        'hybrid' => 'Hybrid',
                        default => 'Chưa cấu hình',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'bam_tai' => 'warning',
                        'hybrid' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('quote_is_active')
                    ->label('Báo giá')
                    ->boolean(),
                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Ngày tạo')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Ngày cập nhật')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                EditAction::make()
                    ->modalWidth(Width::FourExtraLarge),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    \Filament\Actions\ForceDeleteBulkAction::make(),
                    \Filament\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSystemTypes::route('/'),
        ];
    }
}
