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
use Filament\Support\RawJs;
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

    protected static ?string $recordTitleAttribute = 'name_vi';

    protected static function moneyMask(): RawJs
    {
        return RawJs::make('$money($input, \',\', \'.\', 0)');
    }

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
                                            ->label('Tên hệ (cũ)')
                                            ->disabled()
                                            ->dehydrated(false),
                                        Forms\Components\TextInput::make('name_vi')
                                            ->label('Tên hệ (Tiếng Việt)')
                                            ->required()
                                            ->maxLength(255)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn (string $operation, $state, $set) => $operation === 'create' ? $set('slug', Str::slug($state)) : null),
                                        Forms\Components\TextInput::make('name_en')
                                            ->label('Tên hệ (Tiếng Anh)')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('slug')
                                            ->label('Slug')
                                            ->required()
                                            ->maxLength(255)
                                            ->unique(SystemType::class, 'slug', ignoreRecord: true),
                                        Forms\Components\Textarea::make('description_vi')
                                            ->label('Mô tả (Tiếng Việt)')
                                            ->rows(3)
                                            ->columnSpanFull()
                                            ->required(),
                                        Forms\Components\Textarea::make('description_en')
                                            ->label('Mô tả (Tiếng Anh)')
                                            ->rows(3)
                                            ->columnSpanFull()
                                            ->required(),
                                        Forms\Components\Select::make('quote_formula_type')
                                            ->label('Loại công thức báo giá')
                                            ->options([
                                                'bam_tai' => 'Hòa lưới',
                                                'hybrid' => 'Hybrid',
                                                'solar_pump' => 'Solar Pump',
                                            ])
                                            ->helperText('Ví dụ hệ hòa lưới thì chọn "Hòa lưới" để dùng đúng công thức tiền điện, tỷ lệ ban ngày và đơn giá theo kW.')
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
                                    ->description('Với hệ hòa lưới, admin chỉ cần nhập giá 1 số điện, sản lượng trung bình của 1kW/tháng, hệ số điều chỉnh thời giá và tỷ lệ ban ngày mặc định. Phần sản phẩm liên quan sẽ lấy trực tiếp từ danh sách Products theo công suất gần nhất.')
                                    ->columnSpanFull()
                                    ->schema([
                                        Forms\Components\TextInput::make('quote_settings.electric_price')
                                            ->label('Giá 1 số điện trung bình (đồng)')
                                            ->mask(static::moneyMask())
                                            ->stripCharacters('.')
                                            ->numeric()
                                            ->default(2200)
                                            ->helperText('Ví dụ nhập 2200.')
                                            ->required(),
                                        Forms\Components\TextInput::make('quote_settings.yield')
                                            ->label('Sản lượng trung bình của bộ 1kW mỗi tháng (số điện)')
                                            ->numeric()
                                            ->default(120)
                                            ->helperText('Ví dụ nhập 120.')
                                            ->required(),
                                        Forms\Components\TextInput::make('quote_settings.market_factor')
                                            ->label('Hệ số điều chỉnh theo thời giá')
                                            ->numeric()
                                            ->default(1)
                                            ->step('0.01')
                                            ->helperText('Mặc định nhập 1.0. Nếu giá thị trường tăng 10% thì nhập 1.1.')
                                            ->required(),
                                        Forms\Components\TextInput::make('quote_settings.saving_factor')
                                            ->label('Hệ số tiết kiệm/tháng')
                                            ->numeric()
                                            ->default(1)
                                            ->step('0.01')
                                            ->helperText('Có thể để 1.0 nếu chỉ cần ra công suất và chi phí dự kiến.')
                                            ->visible(fn ($get) => $get('quote_formula_type') !== 'bam_tai'),
                                        Forms\Components\TextInput::make('quote_settings.day_ratio_default')
                                            ->label('Tỷ lệ dùng điện ban ngày mặc định (%)')
                                            ->numeric()
                                            ->default(70)
                                            ->step('0.01')
                                            ->helperText('Nhập 30 đến 80. Nếu frontend không truyền, hệ thống sẽ dùng giá trị này.')
                                            ->visible(fn ($get) => in_array($get('quote_formula_type'), ['bam_tai', 'hybrid'], true)),
                                        Forms\Components\TextInput::make('quote_settings.battery_price_per_kwh')
                                            ->label('Giá pin lưu trữ / kWh')
                                            ->mask(static::moneyMask())
                                            ->stripCharacters('.')
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
                                    ->description('Nhập đơn giá theo loại điện và khoảng công suất. Ví dụ 1 pha dưới 10kW là 7.000.000 đồng/kW.')
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
                                                    ->mask(static::moneyMask())
                                                    ->stripCharacters('.')
                                                    ->numeric()
                                                    ->helperText('Ví dụ 7000000, 6800000, 6200000.')
                                                    ->required(),
                                            ])
                                            ->columns(4)
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
            ->recordTitleAttribute('name_vi')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Tên hệ')
                    ->formatStateUsing(fn ($state, $record): string => $record->name_vi ?: $state)
                    ->searchable(),
                Tables\Columns\TextColumn::make('name_en')
                    ->label('Tên EN')
                    ->searchable(),
                Tables\Columns\TextColumn::make('quote_formula_type')
                    ->label('Công thức')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'bam_tai' => 'Hòa lưới',
                        'hybrid' => 'Hybrid',
                        'solar_pump' => 'Solar Pump',
                        default => 'Chưa cấu hình',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'bam_tai' => 'warning',
                        'hybrid' => 'info',
                        'solar_pump' => 'success',
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
