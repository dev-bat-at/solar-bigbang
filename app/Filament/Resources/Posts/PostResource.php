<?php

namespace App\Filament\Resources\Posts;

use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Models\Post;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|\UnitEnum|null $navigationGroup = 'Quản lý Nội dung';

    protected static ?string $modelLabel = 'Bài viết';

    protected static ?string $pluralModelLabel = 'Tin tức & Bài viết';

    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        return ! in_array(static::class, config('admin_menu.hidden_resources', []));
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Nội dung chính')
                    ->description('Tiêu đề và nội dung chi tiết bài viết.')
                    ->icon('heroicon-o-pencil-square')
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Tiêu đề bài viết')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug($state))),
                        Forms\Components\TextInput::make('slug')
                            ->label('Đường dẫn bài viết (URL)')
                            ->required()
                            ->maxLength(255)
                            ->unique(Post::class, 'slug', ignoreRecord: true),
                        Forms\Components\RichEditor::make('content')
                            ->label('Nội dung chính của bài viết')
                            ->required()
                            ->fileAttachmentsDisk('root_public')
                            ->fileAttachmentsDirectory('posts/content')
                            ->extraInputAttributes(['style' => 'min-height: 500px;'])
                            ->columnSpanFull(),
                    ]),

                Section::make('Nội dung bổ sung (Phần 2)')
                    ->description('Cung cấp thêm tiêu đề và nội dung phụ cho bài viết.')
                    ->icon('heroicon-o-document-plus')
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\TextInput::make('title_2')
                            ->label('Tiêu đề phần 2')
                            ->maxLength(255),
                        Forms\Components\RichEditor::make('content_2')
                            ->label('Nội dung phần 2')
                            ->fileAttachmentsDisk('root_public')
                            ->fileAttachmentsDirectory('posts/content_2')
                            ->extraInputAttributes(['style' => 'min-height: 400px;'])
                            ->columnSpanFull(),
                    ]),

                Section::make('Đăng bài & Trạng thái')
                    ->description('Thiết lập xuất bản và ảnh bìa.')
                    ->icon('heroicon-o-photo')
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\FileUpload::make('featured_image')
                            ->label('Ảnh bìa bài viết')
                            ->image()
                            ->disk('root_public')
                            ->directory('posts/thumbnails')
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->label('Trạng thái')
                            ->options([
                                'draft' => 'Nháp',
                                'published' => 'Đã đăng bài',
                                'archived' => 'Lưu trữ',
                            ])
                            ->default('draft')
                            ->required(),
                        Forms\Components\DateTimePicker::make('publish_at')
                            ->label('Ngày giờ đăng bài')
                            ->default(now())
                            ->required(),
                    ]),

                Section::make('Phân loại & Tag')
                    ->description('Thiết lập danh mục giúp tìm kiếm bài viết dễ dàng hơn.')
                    ->icon('heroicon-o-tag')
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\Select::make('tag_id')
                            ->label('Tag')
                            ->relationship('tag', 'name_vi')
                            ->preload()
                            ->searchable()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name_vi')
                                    ->label('Tên Tag (Tiếng Việt)')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('name_en')
                                    ->label('Tên Tag (Tiếng Anh)')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('slug')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\ColorPicker::make('color')
                                    ->label('Màu tag')
                                    ->hex()
                                    ->helperText('API sẽ trả về 0xFF + mã màu của tag.')
                                    ->rule('nullable')
                                    ->rule('regex:/^#?[0-9A-Fa-f]{6}$/'),
                            ]),
                    ]),

                Section::make('Cấu hình SEO')
                    ->description('Thông tin Meta SEO.')
                    ->icon('heroicon-o-magnifying-glass-circle')
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\TextInput::make('seo_title')
                            ->label('Tiêu đề SEO')
                            ->maxLength(255),
                        Forms\Components\Textarea::make('seo_description')
                            ->label('Mô tả SEO')
                            ->rows(3)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('seo_keywords')
                            ->label('Từ khóa')
                            ->maxLength(255),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('featured_image')
                    ->label('Ảnh bìa')
                    ->disk('root_public')
                    ->circular(),
                Tables\Columns\TextColumn::make('title')
                    ->label('Tiêu đề')
                    ->limit(50)
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Nháp',
                        'published' => 'Đã đăng',
                        'archived' => 'Lưu trữ',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'published' => 'success',
                        'archived' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('publish_at')
                    ->label('Ngày đăng')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('tag.name_vi')
                    ->label('Tag')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Lọc trạng thái')
                    ->options([
                        'draft' => 'Nháp',
                        'published' => 'Đã đăng',
                        'archived' => 'Lưu trữ',
                    ]),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPosts::route('/'),
            'create' => CreatePost::route('/create'),
            'edit' => EditPost::route('/{record}/edit'),
        ];
    }
}
