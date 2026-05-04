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
                                            ->live()
                                            ->columnSpan(1),
                                        Forms\Components\Toggle::make('show_calculation_formula')
                                            ->label('Show công thức tính')
                                            ->helperText('Tắt: frontend dùng mặc định 2 trường tỷ lệ ngày/đêm. Bật: frontend dùng Tên + SĐT mặc định và các field bổ sung do admin cấu hình.')
                                            ->inline(false)
                                            ->default(false)
                                            ->live()
                                            ->afterStateUpdated(function ($state, $get, $set): void {
                                                if (! $state && blank($get('quote_settings.ratio_fields'))) {
                                                    $set('quote_settings.ratio_fields', SystemType::defaultQuoteRatioFields());
                                                }
                                            })
                                            ->columnSpan(1),
                                    ])
                                    ->columns(2),
                                Section::make('Cấu hình nhập liệu frontend')
                                    ->description('Bật hoặc tắt là phần field bên dưới đổi ngay, không cần lưu mới thấy.')
                                    ->columnSpanFull()
                                    ->schema([
                                        Forms\Components\Placeholder::make('ratio_mode_note')
                                            ->label('Khi không bật Show công thức tính')
                                            ->content('Admin cấu hình 2 field tỷ lệ ngày/đêm bên dưới, API sẽ trả lại đúng metadata này để frontend render UI.')
                                            ->visible(fn ($get) => ! $get('show_calculation_formula') && $get('quote_formula_type') === 'bam_tai'),
                                        Forms\Components\Repeater::make('quote_settings.ratio_fields')
                                            ->label('2 field tỷ lệ ngày/đêm')
                                            ->default(SystemType::defaultQuoteRatioFields())
                                            ->afterStateHydrated(function ($state, $set): void {
                                                if (blank($state)) {
                                                    $set('quote_settings.ratio_fields', SystemType::defaultQuoteRatioFields());
                                                }
                                            })
                                            ->minItems(2)
                                            ->maxItems(2)
                                            ->reorderable(false)
                                            ->addable(false)
                                            ->deletable(false)
                                            ->visible(fn ($get) => ! $get('show_calculation_formula') && $get('quote_formula_type') === 'bam_tai')
                                            ->schema([
                                                Forms\Components\TextInput::make('key')
                                                    ->label('Key')
                                                    ->required()
                                                    ->maxLength(100)
                                                    ->helperText('Ví dụ: start_day, end_night'),
                                                Forms\Components\TextInput::make('label_vi')
                                                    ->label('Nhãn (Tiếng Việt)')
                                                    ->required()
                                                    ->maxLength(255),
                                                Forms\Components\TextInput::make('label_en')
                                                    ->label('Nhãn (Tiếng Anh)')
                                                    ->required()
                                                    ->maxLength(255),
                                                Forms\Components\TextInput::make('placeholder_vi')
                                                    ->label('Placeholder (Tiếng Việt)')
                                                    ->maxLength(255),
                                                Forms\Components\TextInput::make('placeholder_en')
                                                    ->label('Placeholder (Tiếng Anh)')
                                                    ->maxLength(255),
                                                Forms\Components\TextInput::make('default_value')
                                                    ->label('Giá trị mặc định (%)')
                                                    ->numeric()
                                                    ->step('0.01')
                                                    ->required(),
                                            ])
                                            ->columns(3)
                                            ->columnSpanFull(),
                                        Forms\Components\RichEditor::make('quote_settings.formula_content_vi')
                                            ->label('Nội dung công thức (Tiếng Việt)')
                                            ->toolbarButtons([
                                                'bold',
                                                'italic',
                                                'bulletList',
                                                'orderedList',
                                                'h2',
                                                'h3',
                                                'blockquote',
                                                'undo',
                                                'redo',
                                            ])
                                            ->visible(fn ($get) => (bool) $get('show_calculation_formula'))
                                            ->columnSpanFull(),
                                        Forms\Components\RichEditor::make('quote_settings.formula_content_en')
                                            ->label('Nội dung công thức (Tiếng Anh)')
                                            ->toolbarButtons([
                                                'bold',
                                                'italic',
                                                'bulletList',
                                                'orderedList',
                                                'h2',
                                                'h3',
                                                'blockquote',
                                                'undo',
                                                'redo',
                                            ])
                                            ->visible(fn ($get) => (bool) $get('show_calculation_formula'))
                                            ->columnSpanFull(),
                                        Forms\Components\Repeater::make('quote_request_fields')
                                            ->label('Field bổ sung cho biểu mẫu')
                                            ->defaultItems(0)
                                            ->reorderable()
                                            ->cloneable()
                                            ->collapsed()
                                            ->addActionLabel('Thêm field')
                                            ->visible(fn ($get) => (bool) $get('show_calculation_formula'))
                                            ->schema([
                                                Forms\Components\TextInput::make('key')
                                                    ->label('Key')
                                                    ->required()
                                                    ->helperText('Ví dụ: monthly_bill, roof_area, usage_note')
                                                    ->maxLength(100),
                                                Forms\Components\Select::make('input_type')
                                                    ->label('Loại input')
                                                    ->options([
                                                        'text' => 'Text',
                                                        'number' => 'Number',
                                                        'textarea' => 'Textarea',
                                                    ])
                                                    ->default('text')
                                                    ->required(),
                                                Forms\Components\Toggle::make('required')
                                                    ->label('Bắt buộc')
                                                    ->default(false),
                                                Forms\Components\TextInput::make('label_vi')
                                                    ->label('Nhãn (Tiếng Việt)')
                                                    ->required()
                                                    ->maxLength(255),
                                                Forms\Components\TextInput::make('label_en')
                                                    ->label('Nhãn (Tiếng Anh)')
                                                    ->required()
                                                    ->maxLength(255),
                                                Forms\Components\TextInput::make('placeholder_vi')
                                                    ->label('Placeholder (Tiếng Việt)')
                                                    ->maxLength(255),
                                                Forms\Components\TextInput::make('placeholder_en')
                                                    ->label('Placeholder (Tiếng Anh)')
                                                    ->maxLength(255),
                                            ])
                                            ->columns(3)
                                            ->columnSpanFull(),
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
                                            ->visible(fn ($get) => in_array($get('quote_formula_type'), ['hybrid', 'solar_pump'], true)),
                                        Forms\Components\TextInput::make('quote_settings.day_ratio_default')
                                            ->label('Tỷ lệ dùng điện ban ngày mặc định (%)')
                                            ->numeric()
                                            ->default(70)
                                            ->step('0.01')
                                            ->helperText('Nhập 30 đến 80. Nếu frontend không truyền, hệ thống sẽ dùng giá trị này.')
                                            ->visible(fn ($get) => $get('quote_formula_type') === 'bam_tai' && ! $get('show_calculation_formula')),
                                        Forms\Components\TextInput::make('quote_settings.three_phase_price_factor')
                                            ->label('Hệ số giá điện 3 pha')
                                            ->numeric()
                                            ->default(1.1)
                                            ->step('0.01')
                                            ->helperText('Theo tài liệu hiện tại: điện 3 pha cao hơn 10% so với 1 pha.')
                                            ->visible(fn ($get) => $get('quote_formula_type') === 'hybrid'),
                                        Forms\Components\TextInput::make('quote_settings.three_phase_kw_factor')
                                            ->label('Hệ số quy đổi kWp cho 3 pha')
                                            ->numeric()
                                            ->default(0.91)
                                            ->step('0.01')
                                            ->helperText('Theo tài liệu hiện tại: điện 3 pha nhân thêm 0.91 khi quy đổi kWp.')
                                            ->visible(fn ($get) => $get('quote_formula_type') === 'hybrid'),
                                        Forms\Components\Repeater::make('quote_settings.bill_multiplier_tiers')
                                            ->label('Mốc tiền điện x hệ số')
                                            ->default(SystemType::defaultHybridBillMultiplierTiers())
                                            ->afterStateHydrated(function ($state, $set): void {
                                                if (blank($state)) {
                                                    $set('quote_settings.bill_multiplier_tiers', SystemType::defaultHybridBillMultiplierTiers());
                                                }
                                            })
                                            ->visible(fn ($get) => $get('quote_formula_type') === 'hybrid' && ! $get('show_calculation_formula'))
                                            ->collapsed()
                                            ->cloneable()
                                            ->reorderable()
                                            ->addActionLabel('Thêm mốc tiền điện')
                                            ->schema([
                                                Forms\Components\TextInput::make('min_bill')
                                                    ->label('Từ tiền điện')
                                                    ->mask(static::moneyMask())
                                                    ->stripCharacters('.')
                                                    ->numeric()
                                                    ->required(),
                                                Forms\Components\TextInput::make('max_bill')
                                                    ->label('Đến tiền điện')
                                                    ->mask(static::moneyMask())
                                                    ->stripCharacters('.')
                                                    ->numeric()
                                                    ->helperText('Để trống nếu là mức cuối.'),
                                                Forms\Components\TextInput::make('multiplier')
                                                    ->label('Hệ số nhân')
                                                    ->numeric()
                                                    ->step('0.01')
                                                    ->required(),
                                            ])
                                            ->columns(3)
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2),
                            ]),

                        \Filament\Schemas\Components\Tabs\Tab::make('Đơn giá theo mốc')
                            ->icon('heroicon-o-banknotes')
                            ->visible(fn ($get) => ! (bool) $get('show_calculation_formula') && $get('quote_formula_type') !== 'hybrid')
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
                Tables\Columns\IconColumn::make('show_calculation_formula')
                    ->label('Show CT')
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
