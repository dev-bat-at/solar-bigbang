<?php

namespace App\Filament\Resources\Products;

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\Pages\ViewProduct;
use App\Models\Product;
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
use Filament\Tables\Columns\BadgeColumn;
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

    protected static ?int $navigationSort = 1;

    // ─── Money mask (same as SystemType) ────────────────────────────────────
    protected static function moneyMask(): RawJs
    {
        return RawJs::make('$money($input, \',\', \'.\', 0)');
    }

    // ─── Form ───────────────────────────────────────────────────────────────
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('product_tabs')
                ->tabs([

                    // ── Tab 1: Thông tin cơ bản ──────────────────────────
                    Tab::make('Thông tin cơ bản')
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            Section::make('Định danh sản phẩm')
                                ->description('Mã, tên và slug dùng để nhận diện sản phẩm trong hệ thống.')
                                ->icon('heroicon-o-tag')
                                ->columnSpanFull()
                                ->schema([
                                    TextInput::make('code')
                                        ->label('Mã sản phẩm')
                                        ->required()
                                        ->unique(Product::class, 'code', ignoreRecord: true)
                                        ->maxLength(100)
                                        ->placeholder('VD: SP-PANEL-001'),
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
                                ])->columns(2),

                            Section::make('Giới thiệu ngắn')
                                ->description('Dòng mô tả ngắn hiển thị bên dưới tên sản phẩm (không bắt buộc).')
                                ->icon('heroicon-o-chat-bubble-left-ellipsis')
                                ->columnSpanFull()
                                ->schema([
                                    TextInput::make('tagline_vi')
                                        ->label('Tagline (Tiếng Việt)')
                                        ->maxLength(255)
                                        ->placeholder('VD: Lắp đặt tận nơi, bảo hành 25 năm'),
                                    TextInput::make('tagline_en')
                                        ->label('Tagline (Tiếng Anh)')
                                        ->maxLength(255)
                                        ->placeholder('VD: On-site installation, 25-year warranty'),
                                ])->columns(2),

                            Section::make('Trạng thái')
                                ->description('Kiểm soát hiển thị sản phẩm trên app.')
                                ->icon('heroicon-o-eye')
                                ->columnSpanFull()
                                ->schema([
                                    Select::make('status')
                                        ->label('Trạng thái')
                                        ->options([
                                            'draft'     => 'Nháp',
                                            'published' => 'Đã xuất bản',
                                            'hidden'    => 'Ẩn',
                                        ])
                                        ->default('draft')
                                        ->required()
                                        ->native(false),
                                    Toggle::make('is_best_seller')
                                        ->label('Bán chạy')
                                        ->helperText('Bật để đánh dấu sản phẩm này là bán chạy.')
                                        ->inline(false)
                                        ->default(false),
                                ])->columns(2),
                        ]),

                    // ── Tab 2: Ảnh sản phẩm ──────────────────────────────
                    Tab::make('Ảnh sản phẩm')
                        ->icon('heroicon-o-photo')
                        ->schema([
                            Section::make('Hình ảnh')
                                ->description('Upload nhiều ảnh. Ảnh đầu tiên trong danh sách sẽ được dùng làm ảnh chính.')
                                ->icon('heroicon-o-camera')
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
                                        ->hiddenOn('view')
                                        ->helperText('Kéo để sắp xếp thứ tự. Ảnh đầu tiên sẽ là ảnh chính (được đánh dấu ★).'),
                                    ViewField::make('images')
                                        ->hiddenLabel()
                                        ->view('filament.forms.components.product-gallery')
                                        ->viewData(fn (?Product $record): array => [
                                            'images' => $record?->images ?? [],
                                        ])
                                        ->visibleOn('view')
                                        ->columnSpanFull(),
                                ]),
                        ]),

                    // ── Tab 3: Giá thành ─────────────────────────────────
                    Tab::make('Giá thành')
                        ->icon('heroicon-o-banknotes')
                        ->schema([
                            Section::make('Thông tin giá')
                                ->description('Giá tham khảo và đơn vị hiển thị.')
                                ->icon('heroicon-o-currency-dollar')
                                ->columnSpanFull()
                                ->schema([
                                    TextInput::make('price')
                                        ->label('Giá thành (VNĐ)')
                                        ->mask(static::moneyMask())
                                        ->stripCharacters('.')
                                        ->numeric()
                                        ->required()
                                        ->placeholder('VD: 70.000'),
                                    TextInput::make('price_unit_vi')
                                        ->label('Đơn vị giá (Tiếng Việt)')
                                        ->required()
                                        ->maxLength(100)
                                        ->placeholder('VD: Triệu đồng / kWp'),
                                    TextInput::make('price_unit_en')
                                        ->label('Đơn vị giá (Tiếng Anh)')
                                        ->required()
                                        ->maxLength(100)
                                        ->placeholder('VD: Million VND / kWp'),
                                ])->columns(2),
                        ]),

                    // ── Tab 4: Thông số kỹ thuật cơ bản ─────────────────
                    Tab::make('Thông số KT')
                        ->icon('heroicon-o-beaker')
                        ->schema([
                            Section::make('Thông số kỹ thuật chính')
                                ->description('Công suất, hiệu suất và bảo hành của sản phẩm.')
                                ->icon('heroicon-o-cpu-chip')
                                ->columnSpanFull()
                                ->schema([
                                    TextInput::make('power')
                                        ->label('Công suất')
                                        ->required()
                                        ->maxLength(100)
                                        ->placeholder('VD: 550Wp'),
                                    TextInput::make('efficiency')
                                        ->label('Hiệu suất')
                                        ->required()
                                        ->maxLength(100)
                                        ->placeholder('VD: 21.3%'),
                                    TextInput::make('warranty')
                                        ->label('Bảo hành')
                                        ->required()
                                        ->maxLength(100)
                                        ->placeholder('VD: 25 năm'),
                                ])->columns(3),

                            Section::make('Thông số chi tiết')
                                ->description('Thêm các thông số kỹ thuật tùy ý (tên field + giá trị).')
                                ->icon('heroicon-o-list-bullet')
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
                                            TextInput::make('label_vi')
                                                ->label('Tên thông số (Tiếng Việt)')
                                                ->required()
                                                ->maxLength(255)
                                                ->placeholder('VD: Công suất tấm pin'),
                                            TextInput::make('label_en')
                                                ->label('Tên thông số (Tiếng Anh)')
                                                ->required()
                                                ->maxLength(255)
                                                ->placeholder('VD: Panel Power'),
                                            TextInput::make('value_vi')
                                                ->label('Giá trị (Tiếng Việt)')
                                                ->required()
                                                ->maxLength(255)
                                                ->placeholder('VD: 550Wp'),
                                            TextInput::make('value_en')
                                                ->label('Giá trị (Tiếng Anh)')
                                                ->required()
                                                ->maxLength(255)
                                                ->placeholder('VD: 550Wp'),
                                        ])->columns(2)->columnSpanFull(),
                                ]),
                        ]),

                    // ── Tab 5: Mô tả ─────────────────────────────────────
                    Tab::make('Mô tả')
                        ->icon('heroicon-o-document-text')
                        ->schema([
                            Section::make('Mô tả sản phẩm')
                                ->description('Nội dung mô tả chi tiết.')
                                ->icon('heroicon-o-pencil-square')
                                ->columnSpanFull()
                                ->schema([
                                    RichEditor::make('description_vi')
                                        ->label('Mô tả (Tiếng Việt)')
                                        ->required()
                                        ->columnSpanFull()
                                        ->toolbarButtons([
                                            'bold', 'italic', 'underline',
                                            'bulletList', 'orderedList',
                                            'h2', 'h3',
                                            'link',
                                            'undo', 'redo',
                                        ]),
                                    RichEditor::make('description_en')
                                        ->label('Mô tả (Tiếng Anh)')
                                        ->required()
                                        ->columnSpanFull()
                                        ->toolbarButtons([
                                            'bold', 'italic', 'underline',
                                            'bulletList', 'orderedList',
                                            'h2', 'h3',
                                            'link',
                                            'undo', 'redo',
                                        ]),
                                ]),
                        ]),

                    // ── Tab 6: Tài liệu ───────────────────────────────────
                    Tab::make('Tài liệu')
                        ->icon('heroicon-o-paper-clip')
                        ->schema([
                            Section::make('Tài liệu sản phẩm')
                                ->description('Upload tài liệu kỹ thuật: PDF, Word, Excel.')
                                ->icon('heroicon-o-document-arrow-down')
                                ->columnSpanFull()
                                ->schema([
                                    Repeater::make('documents')
                                        ->label('Tài liệu')
                                        ->defaultItems(0)
                                        ->reorderable()
                                        ->addActionLabel('+ Thêm tài liệu')
                                        ->required()
                                        ->minItems(1)
                                        ->schema([
                                            TextInput::make('name_vi')
                                                ->label('Tên tài liệu (Tiếng Việt)')
                                                ->required()
                                                ->maxLength(255)
                                                ->placeholder('VD: Datasheet tấm pin 550Wp'),
                                            TextInput::make('name_en')
                                                ->label('Tên tài liệu (Tiếng Anh)')
                                                ->required()
                                                ->maxLength(255)
                                                ->placeholder('VD: Panel Datasheet 550Wp'),
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
                                                ->maxSize(20480)
                                                ->helperText('Cho phép: PDF, Word (.doc/.docx), Excel (.xls/.xlsx). Tối đa 20MB.'),
                                        ])->columns(2)->columnSpanFull(),
                                ]),
                        ]),

                    // ── Tab 7: FAQ ────────────────────────────────────────
                    Tab::make('FAQ')
                        ->icon('heroicon-o-question-mark-circle')
                        ->schema([
                            Section::make('Câu hỏi thường gặp')
                                ->description('Thêm các câu hỏi và câu trả lời liên quan đến sản phẩm.')
                                ->icon('heroicon-o-chat-bubble-oval-left-ellipsis')
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
                                            TextInput::make('question_vi')
                                                ->label('Câu hỏi (Tiếng Việt)')
                                                ->required()
                                                ->maxLength(500)
                                                ->placeholder('VD: Tấm pin có bao nhiêu năm bảo hành?'),
                                            TextInput::make('question_en')
                                                ->label('Câu hỏi (Tiếng Anh)')
                                                ->required()
                                                ->maxLength(500)
                                                ->placeholder('VD: How many years of warranty does the panel have?'),
                                            Textarea::make('answer_vi')
                                                ->label('Câu trả lời (Tiếng Việt)')
                                                ->required()
                                                ->rows(3)
                                                ->maxLength(2000),
                                            Textarea::make('answer_en')
                                                ->label('Câu trả lời (Tiếng Anh)')
                                                ->required()
                                                ->rows(3)
                                                ->maxLength(2000),
                                        ])->columns(2)->columnSpanFull(),
                                ]),
                        ]),

                ])->columnSpanFull(),
        ]);
    }

    // ─── Infolist ───────────────────────────────────────────────────────────
    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('Thông tin cơ bản')
                ->description('Mã, tên, trạng thái và cài đặt hiển thị của sản phẩm.')
                ->icon('heroicon-o-information-circle')
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('code')
                        ->label('Mã sản phẩm')
                        ->badge()
                        ->color('info'),
                    TextEntry::make('status')
                        ->label('Trạng thái')
                        ->badge()
                        ->formatStateUsing(fn (string $state): string => match ($state) {
                            'draft'     => 'Nháp',
                            'published' => 'Đã xuất bản',
                            'hidden'    => 'Ẩn',
                            default     => $state,
                        })
                        ->color(fn (string $state): string => match ($state) {
                            'draft'     => 'gray',
                            'published' => 'success',
                            'hidden'    => 'warning',
                            default     => 'gray',
                        }),
                    TextEntry::make('name_vi')
                        ->label('Tên (Tiếng Việt)'),
                    TextEntry::make('name_en')
                        ->label('Tên (Tiếng Anh)'),
                    TextEntry::make('slug')
                        ->label('Slug'),
                    IconEntry::make('is_best_seller')
                        ->label('Bán chạy')
                        ->boolean(),
                    TextEntry::make('tagline_vi')
                        ->label('Tagline (Tiếng Việt)')
                        ->placeholder('Chưa có')
                        ->columnSpanFull(),
                    TextEntry::make('tagline_en')
                        ->label('Tagline (Tiếng Anh)')
                        ->placeholder('Chưa có')
                        ->columnSpanFull(),
                ])->columns(3),

            Section::make('Hình ảnh sản phẩm')
                ->description('Ảnh hiển thị trên app. Ảnh đầu tiên là ảnh chính.')
                ->icon('heroicon-o-photo')
                ->columnSpanFull()
                ->schema([
                    ViewEntry::make('images')
                        ->hiddenLabel()
                        ->view('filament.infolists.entries.product-gallery')
                        ->columnSpanFull(),
                ]),

            Section::make('Giá thành')
                ->description('Giá và đơn vị tính.')
                ->icon('heroicon-o-banknotes')
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('price')
                        ->label('Giá thành')
                        ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', '.') . ' đ' : '—'),
                    TextEntry::make('price_unit_vi')
                        ->label('Đơn vị (Tiếng Việt)')
                        ->placeholder('—'),
                    TextEntry::make('price_unit_en')
                        ->label('Đơn vị (Tiếng Anh)')
                        ->placeholder('—'),
                ])->columns(3),

            Section::make('Thông số kỹ thuật chính')
                ->description('Công suất, hiệu suất, bảo hành.')
                ->icon('heroicon-o-cpu-chip')
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('power')
                        ->label('Công suất')
                        ->placeholder('—'),
                    TextEntry::make('efficiency')
                        ->label('Hiệu suất')
                        ->placeholder('—'),
                    TextEntry::make('warranty')
                        ->label('Bảo hành')
                        ->placeholder('—'),
                ])->columns(3),

            Section::make('Thông số chi tiết')
                ->description('Danh sách thông số kỹ thuật.')
                ->icon('heroicon-o-list-bullet')
                ->columnSpanFull()
                ->schema([
                    ViewEntry::make('specifications')
                        ->hiddenLabel()
                        ->view('filament.infolists.entries.product-specifications')
                        ->columnSpanFull(),
                ]),

            Section::make('Mô tả')
                ->description('Nội dung mô tả sản phẩm.')
                ->icon('heroicon-o-document-text')
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('description_vi')
                        ->label('Mô tả (Tiếng Việt)')
                        ->html()
                        ->columnSpanFull()
                        ->placeholder('Chưa có'),
                    TextEntry::make('description_en')
                        ->label('Mô tả (Tiếng Anh)')
                        ->html()
                        ->columnSpanFull()
                        ->placeholder('Chưa có'),
                ]),

            Section::make('Tài liệu')
                ->description('Các tài liệu kỹ thuật đính kèm.')
                ->icon('heroicon-o-paper-clip')
                ->columnSpanFull()
                ->schema([
                    ViewEntry::make('documents')
                        ->hiddenLabel()
                        ->view('filament.infolists.entries.product-documents')
                        ->columnSpanFull(),
                ]),

            Section::make('FAQ')
                ->description('Câu hỏi thường gặp.')
                ->icon('heroicon-o-question-mark-circle')
                ->columnSpanFull()
                ->schema([
                    ViewEntry::make('faqs')
                        ->hiddenLabel()
                        ->view('filament.infolists.entries.product-faqs')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    // ─── Table ──────────────────────────────────────────────────────────────
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Mã SP')
                    ->searchable()
                    ->badge()
                    ->color('info'),
                TextColumn::make('name_vi')
                    ->label('Tên sản phẩm')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('price')
                    ->label('Giá')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', '.') . ' đ' : '—')
                    ->sortable(),
                TextColumn::make('power')
                    ->label('Công suất')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft'     => 'Nháp',
                        'published' => 'Đã xuất bản',
                        'hidden'    => 'Ẩn',
                        default     => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'draft'     => 'gray',
                        'published' => 'success',
                        'hidden'    => 'warning',
                        default     => 'gray',
                    }),
                IconColumn::make('is_best_seller')
                    ->label('Bán chạy')
                    ->boolean()
                    ->trueIcon('heroicon-o-fire')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('danger')
                    ->falseColor('gray'),
                TextColumn::make('created_at')
                    ->label('Ngày tạo')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Trạng thái')
                    ->options([
                        'draft'     => 'Nháp',
                        'published' => 'Đã xuất bản',
                        'hidden'    => 'Ẩn',
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

    // ─── Eloquent Query ─────────────────────────────────────────────────────
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
            'index'  => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'view'   => ViewProduct::route('/{record}'),
            'edit'   => EditProduct::route('/{record}/edit'),
        ];
    }
}
