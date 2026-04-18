<?php

namespace App\Filament\Resources\Products;

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\Pages\ViewProduct;
use App\Models\Product;
use App\Models\ProductCategory;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ViewField;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'Sản phẩm';

    protected static ?string $modelLabel = 'Sản phẩm';

    protected static ?string $pluralModelLabel = 'Sản phẩm';

    protected static ?string $recordTitleAttribute = 'name_vi';

    protected static string|\UnitEnum|null $navigationGroup = 'Nội dung & Sản phẩm';

    protected static ?int $navigationSort = 2;

    protected static function moneyMask(): RawJs
    {
        return RawJs::make('$money($input, \',\', \'.\', 0)');
    }

    protected static function topLevelCategoryOptions(): array
    {
        return ProductCategory::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name_vi')
            ->pluck('name_vi', 'id')
            ->all();
    }

    protected static function categoryHasChildren(?int $categoryId): bool
    {
        if (! $categoryId) {
            return false;
        }

        return ProductCategory::query()
            ->where('parent_id', $categoryId)
            ->where('is_active', true)
            ->exists();
    }

    protected static function subcategoryOptions(?int $categoryId): array
    {
        if (! $categoryId) {
            return [];
        }

        return ProductCategory::query()
            ->where('parent_id', $categoryId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name_vi')
            ->pluck('name_vi', 'id')
            ->all();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('product_tabs')
                ->tabs([
                    Tab::make('Thông tin cơ bản')
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            Section::make('Định danh sản phẩm')
                                ->description('Mã, tên, slug và phân loại dùng để nhận diện sản phẩm trong hệ thống.')
                                ->icon('heroicon-o-tag')
                                ->columnSpanFull()
                                ->schema([
                                    TextInput::make('code')
                                        ->label('Mã sản phẩm')
                                        ->required()
                                        ->unique(Product::class, 'code', ignoreRecord: true)
                                        ->maxLength(100),
                                    TextInput::make('slug')
                                        ->label('Slug')
                                        ->required()
                                        ->unique(Product::class, 'slug', ignoreRecord: true)
                                        ->maxLength(255)
                                        ->live(onBlur: true),
                                    TextInput::make('name_vi')
                                        ->label('Tên sản phẩm (Tiếng Việt)')
                                        ->required()
                                        ->maxLength(255)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function (string $operation, $state, $set) {
                                            if ($operation === 'create') {
                                                $set('slug', Str::slug($state));
                                            }
                                        }),
                                    TextInput::make('name_en')
                                        ->label('Tên sản phẩm (Tiếng Anh)')
                                        ->required()
                                        ->maxLength(255),
                                    Select::make('product_category_id')
                                        ->label('Loại sản phẩm')
                                        ->options(fn () => static::topLevelCategoryOptions())
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(fn ($state, $set) => $set('product_subcategory_id', null)),
                                    Select::make('product_subcategory_id')
                                        ->label('Danh mục con')
                                        ->options(fn ($get) => static::subcategoryOptions($get('product_category_id')))
                                        ->searchable()
                                        ->preload()
                                        ->visible(fn ($get) => static::categoryHasChildren($get('product_category_id')))
                                        ->required(fn ($get) => static::categoryHasChildren($get('product_category_id'))),
                                ])->columns(2),
                            Section::make('Giới thiệu ngắn')
                                ->columnSpanFull()
                                ->schema([
                                    TextInput::make('tagline_vi')->label('Tagline (Tiếng Việt)')->maxLength(255),
                                    TextInput::make('tagline_en')->label('Tagline (Tiếng Anh)')->maxLength(255),
                                ])->columns(2),
                            Section::make('Trạng thái')
                                ->columnSpanFull()
                                ->schema([
                                    Select::make('status')
                                        ->label('Trạng thái')
                                        ->options([
                                            'draft' => 'Nháp',
                                            'published' => 'Đã xuất bản',
                                            'hidden' => 'Ẩn',
                                        ])
                                        ->default('draft')
                                        ->required()
                                        ->native(false),
                                    Toggle::make('is_best_seller')
                                        ->label('Bán chạy')
                                        ->inline(false)
                                        ->default(false),
                                ])->columns(2),
                        ]),
                    Tab::make('Ảnh sản phẩm')
                        ->icon('heroicon-o-photo')
                        ->schema([
                            Section::make('Hình ảnh')
                                ->columnSpanFull()
                                ->schema([
                                    FileUpload::make('images')
                                        ->label('Ảnh sản phẩm')
                                        ->multiple()
                                        ->image()
                                        ->panelLayout('grid')
                                        ->reorderable()
                                        ->disk('root_public')
                                        ->directory('products')
                                        ->maxFiles(20)
                                        ->required()
                                        ->hiddenOn('view'),
                                    ViewField::make('images')
                                        ->hiddenLabel()
                                        ->view('filament.forms.components.product-gallery')
                                        ->viewData(fn (?Product $record): array => ['images' => $record?->images ?? []])
                                        ->visibleOn('view')
                                        ->columnSpanFull(),
                                ]),
                        ]),
                    Tab::make('Giá thành')
                        ->icon('heroicon-o-banknotes')
                        ->schema([
                            Section::make('Thông tin giá')
                                ->columnSpanFull()
                                ->schema([
                                    Toggle::make('is_price_contact')
                                        ->label('Liên hệ thay vì nhập giá')
                                        ->live(),
                                    TextInput::make('price')
                                        ->label('Giá thành (VNĐ)')
                                        ->mask(static::moneyMask())
                                        ->stripCharacters('.')
                                        ->numeric()
                                        ->hidden(fn ($get) => $get('is_price_contact'))
                                        ->required(fn ($get) => !$get('is_price_contact')),
                                    TextInput::make('price_unit_vi')->label('Đơn vị giá (Tiếng Việt)')->required()->maxLength(100),
                                    TextInput::make('price_unit_en')->label('Đơn vị giá (Tiếng Anh)')->required()->maxLength(100),
                                ])->columns(2),
                        ]),
                    Tab::make('Thông số KT')
                        ->icon('heroicon-o-beaker')
                        ->schema([
                            Section::make('Thông số kỹ thuật chính')
                                ->columnSpanFull()
                                ->schema([
                                    TextInput::make('power')
                                        ->label('Công suất')
                                        ->helperText('Nhập rõ như 5kW, 5.5kW, 10kW, 550Wp. Nếu là sản phẩm 3 pha hoặc 1 pha, nên ghi rõ trong tên hoặc tagline để hệ thống lọc đúng.')
                                        ->required()
                                        ->maxLength(100),
                                    TextInput::make('efficiency')->label('Hiệu suất')->required()->maxLength(100),
                                    TextInput::make('warranty_vi')->label('Bảo hành (Tiếng Việt)')->required()->maxLength(100),
                                    TextInput::make('warranty_en')->label('Bảo hành (Tiếng Anh)')->required()->maxLength(100),
                                ])->columns(3),
                            Section::make('Thông số chi tiết')
                                ->columnSpanFull()
                                ->schema([
                                    Repeater::make('specifications')
                                        ->label('Thông số')
                                        ->defaultItems(0)
                                        ->reorderable()
                                        ->cloneable()
                                        ->addActionLabel('+ Thêm thông số')
                                        ->required()
                                        ->minItems(1)
                                        ->schema([
                                            TextInput::make('label_vi')->label('Tên thông số (Tiếng Việt)')->required()->maxLength(255),
                                            TextInput::make('label_en')->label('Tên thông số (Tiếng Anh)')->required()->maxLength(255),
                                            TextInput::make('value_vi')->label('Giá trị (Tiếng Việt)')->required()->maxLength(255),
                                            TextInput::make('value_en')->label('Giá trị (Tiếng Anh)')->required()->maxLength(255),
                                        ])->columns(2)->columnSpanFull(),
                                ]),
                        ]),
                    Tab::make('Mô tả')
                        ->icon('heroicon-o-document-text')
                        ->schema([
                            Section::make('Mô tả sản phẩm')
                                ->columnSpanFull()
                                ->schema([
                                    RichEditor::make('description_vi')
                                        ->label('Mô tả (Tiếng Việt)')
                                        ->required()
                                        ->columnSpanFull(),
                                    RichEditor::make('description_en')
                                        ->label('Mô tả (Tiếng Anh)')
                                        ->required()
                                        ->columnSpanFull(),
                                ]),
                        ]),
                    Tab::make('Tài liệu')
                        ->icon('heroicon-o-paper-clip')
                        ->schema([
                            Section::make('Tài liệu sản phẩm')
                                ->columnSpanFull()
                                ->schema([
                                    Repeater::make('documents')
                                        ->label('Tài liệu')
                                        ->defaultItems(0)
                                        ->reorderable()
                                        ->addActionLabel('+ Thêm tài liệu')
                                        ->schema([
                                            TextInput::make('name_vi')->label('Tên tài liệu (Tiếng Việt)')->required()->maxLength(255),
                                            TextInput::make('name_en')->label('Tên tài liệu (Tiếng Anh)')->required()->maxLength(255),
                                            FileUpload::make('path')
                                                ->label('File tài liệu')
                                                ->required()
                                                ->disk('root_public')
                                                ->directory('products/documents')
                                                ->acceptedFileTypes([
                                                    'application/pdf',
                                                    'application/msword',
                                                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                                    'application/vnd.ms-excel',
                                                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                                ])
                                                ->maxSize(20480),
                                        ])->columns(2)->columnSpanFull(),
                                ]),
                        ]),
                    Tab::make('FAQ')
                        ->icon('heroicon-o-question-mark-circle')
                        ->schema([
                            Section::make('Câu hỏi thường gặp')
                                ->columnSpanFull()
                                ->schema([
                                    Repeater::make('faqs')
                                        ->label('FAQ')
                                        ->defaultItems(0)
                                        ->reorderable()
                                        ->cloneable()
                                        ->addActionLabel('+ Thêm câu hỏi')
                                        ->required()
                                        ->minItems(1)
                                        ->schema([
                                            TextInput::make('question_vi')->label('Câu hỏi (Tiếng Việt)')->required()->maxLength(500),
                                            TextInput::make('question_en')->label('Câu hỏi (Tiếng Anh)')->required()->maxLength(500),
                                            Textarea::make('answer_vi')->label('Câu trả lời (Tiếng Việt)')->required()->rows(3)->maxLength(2000),
                                            Textarea::make('answer_en')->label('Câu trả lời (Tiếng Anh)')->required()->rows(3)->maxLength(2000),
                                        ])->columns(2)->columnSpanFull(),
                                ]),
                        ]),
                ])->columnSpanFull(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Thông tin cơ bản')
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('code')->label('Mã sản phẩm')->badge()->color('info'),
                    TextEntry::make('status')
                        ->label('Trạng thái')
                        ->badge()
                        ->formatStateUsing(fn (string $state): string => match ($state) {
                            'draft' => 'Nháp',
                            'published' => 'Đã xuất bản',
                            'hidden' => 'Ẩn',
                            default => $state,
                        }),
                    TextEntry::make('name_vi')->label('Tên (Tiếng Việt)'),
                    TextEntry::make('name_en')->label('Tên (Tiếng Anh)'),
                    TextEntry::make('productCategory.name_vi')->label('Loại sản phẩm')->placeholder('—'),
                    TextEntry::make('productSubcategory.name_vi')->label('Danh mục con')->placeholder('—'),
                    TextEntry::make('slug')->label('Slug'),
                    IconEntry::make('is_best_seller')->label('Bán chạy')->boolean(),
                    TextEntry::make('tagline_vi')->label('Tagline (Tiếng Việt)')->placeholder('Chưa có')->columnSpanFull(),
                    TextEntry::make('tagline_en')->label('Tagline (Tiếng Anh)')->placeholder('Chưa có')->columnSpanFull(),
                ])->columns(3),
            Section::make('Hình ảnh sản phẩm')
                ->columnSpanFull()
                ->schema([
                    ViewEntry::make('images')
                        ->hiddenLabel()
                        ->view('filament.infolists.entries.product-gallery')
                        ->columnSpanFull(),
                ]),
            Section::make('Giá thành')
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('price')->label('Giá thành')->formatStateUsing(fn ($state, $record) => $record->is_price_contact ? 'Liên hệ' : ($state ? number_format($state, 0, ',', '.') . ' đ' : '—')),
                    TextEntry::make('price_unit_vi')->label('Đơn vị (Tiếng Việt)')->placeholder('—'),
                    TextEntry::make('price_unit_en')->label('Đơn vị (Tiếng Anh)')->placeholder('—'),
                ])->columns(3),
            Section::make('Thông số kỹ thuật chính')
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('power')->label('Công suất')->placeholder('—'),
                    TextEntry::make('efficiency')->label('Hiệu suất')->placeholder('—'),
                    TextEntry::make('warranty_vi')->label('Bảo hành (Tiếng Việt)')->placeholder('—'),
                    TextEntry::make('warranty_en')->label('Bảo hành (Tiếng Anh)')->placeholder('—'),
                ])->columns(3),
            Section::make('Thông số chi tiết')
                ->columnSpanFull()
                ->schema([
                    ViewEntry::make('specifications')
                        ->hiddenLabel()
                        ->view('filament.infolists.entries.product-specifications')
                        ->columnSpanFull(),
                ]),
            Section::make('Mô tả')
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('description_vi')->label('Mô tả (Tiếng Việt)')->html()->columnSpanFull()->placeholder('Chưa có'),
                    TextEntry::make('description_en')->label('Mô tả (Tiếng Anh)')->html()->columnSpanFull()->placeholder('Chưa có'),
                ]),
            Section::make('Tài liệu')
                ->columnSpanFull()
                ->schema([
                    ViewEntry::make('documents')
                        ->hiddenLabel()
                        ->view('filament.infolists.entries.product-documents')
                        ->columnSpanFull(),
                ]),
            Section::make('FAQ')
                ->columnSpanFull()
                ->schema([
                    ViewEntry::make('faqs')
                        ->hiddenLabel()
                        ->view('filament.infolists.entries.product-faqs')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label('Mã SP')->searchable()->badge()->color('info'),
                TextColumn::make('name_vi')->label('Tên sản phẩm')->searchable()->sortable(),
                TextColumn::make('productCategory.name_vi')->label('Loại')->searchable()->sortable()->badge(),
                TextColumn::make('productSubcategory.name_vi')->label('Mục con')->searchable()->sortable()->placeholder('—')->toggleable(),
                TextColumn::make('price')->label('Giá')->formatStateUsing(fn ($state, $record) => $record->is_price_contact ? 'Liên hệ' : ($state ? number_format($state, 0, ',', '.') . ' đ' : '—'))->sortable(),
                TextColumn::make('power')->label('Công suất')->searchable()->toggleable(),
                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Nháp',
                        'published' => 'Đã xuất bản',
                        'hidden' => 'Ẩn',
                        default => $state,
                    }),
                IconColumn::make('is_best_seller')
                    ->label('Bán chạy')
                    ->boolean()
                    ->trueIcon('heroicon-o-fire')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('danger')
                    ->falseColor('gray'),
                TextColumn::make('created_at')->label('Ngày tạo')->dateTime('d/m/Y H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('product_category_id')
                    ->label('Loại sản phẩm')
                    ->relationship('productCategory', 'name_vi')
                    ->searchable()
                    ->preload()
                    ->placeholder('Tất cả'),
                SelectFilter::make('product_subcategory_id')
                    ->label('Danh mục con')
                    ->relationship('productSubcategory', 'name_vi')
                    ->searchable()
                    ->preload()
                    ->placeholder('Tất cả'),
                SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options([
                        'draft' => 'Nháp',
                        'published' => 'Đã xuất bản',
                        'hidden' => 'Ẩn',
                    ]),
                SelectFilter::make('is_best_seller')
                    ->label('Bán chạy')
                    ->options([
                        '1' => 'Bán chạy',
                        '0' => 'Không',
                    ]),
                TrashedFilter::make(),
            ])
            ->actions([
                ViewAction::make(),
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'view' => ViewProduct::route('/{record}'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}
