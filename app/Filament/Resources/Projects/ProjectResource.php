<?php

namespace App\Filament\Resources\Projects;

use App\Models\Province;
use App\Models\Project;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Support\RawJs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Filament\Resources\Projects\Pages\CreateProject;
use App\Filament\Resources\Projects\Pages\EditProject;
use App\Filament\Resources\Projects\Pages\ViewProject;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Công trình';

    protected static ?string $modelLabel = 'Công trình';

    protected static ?string $pluralModelLabel = 'Công trình đã thi công';

    protected static string|\UnitEnum|null $navigationGroup = 'Quản lý Đối tác & Lead';

    protected static ?int $navigationSort = 6;

    protected static function moneyMask(): RawJs
    {
        return RawJs::make('$money($input, \',\', \'.\', 0)');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return !in_array(static::class, config('admin_menu.hidden_resources', []));
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Thông tin định danh')
                    ->description('Cơ bản về dự án/công trình')
                    ->icon('heroicon-o-information-circle')
                    ->columnSpanFull()
                    ->schema([
                        Select::make('dealer_id')
                            ->label('Đại lý thi công')
                            ->relationship('dealer', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('system_type_id')
                            ->label('Hệ')
                            ->relationship('systemType', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->label('Tên hệ')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn(string $operation, $state, $set) => $operation === 'create' ? $set('slug', \Illuminate\Support\Str::slug($state)) : null),
                                TextInput::make('slug')
                                    ->label('Slug')
                                    ->required()
                                    ->unique('system_types', 'slug'),
                            ]),
                        TextInput::make('title')
                            ->label('Tên công trình')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('capacity')
                            ->label('Công suất')
                            ->maxLength(255)
                            ->placeholder('Ví dụ: 5 kWp'),
                        TextInput::make('price')
                            ->label('Giá tiền')
                            ->mask(static::moneyMask())
                            ->stripCharacters('.')
                            ->numeric()
                            ->minValue(0)
                            ->suffix('VND'),
                        \Filament\Forms\Components\DatePicker::make('completion_date')
                            ->label('Thời gian hoàn thành')
                            ->displayFormat('d/m/Y'),
                        Select::make('province_id')
                            ->label('Tỉnh/Thành')
                            ->options(fn () => Province::query()
                                ->whereNull('parent_id')
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->preload(),
                        TextInput::make('address')
                            ->label('Địa điểm thi công (Địa chỉ)')
                            ->maxLength(255),
                        Textarea::make('description')
                            ->label('Mô tả')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('Hình ảnh')
                    ->description('Hình ảnh thi công thực tế')
                    ->icon('heroicon-o-photo')
                    ->columnSpanFull()
                    ->schema([
                        FileUpload::make('images')
                            ->label('Ảnh công trình')
                            ->multiple()
                            ->image()
                            ->acceptedFileTypes([
                                'image/jpeg',
                                'image/png',
                                'image/webp',
                                'image/gif',
                                'image/heic',
                                'image/heif',
                            ])
                            ->panelLayout('grid')
                            ->reorderable()
                            ->disk('root_public')
                            ->directory('projects')
                            ->maxFiles(10)
                            ->required(),
                    ]),

                Section::make('Kiểm duyệt')
                    ->description('Trạng thái phê duyệt công trình')
                    ->icon('heroicon-o-shield-check')
                    ->columnSpanFull()
                    ->schema([
                        Select::make('status')
                            ->label('Trạng thái')
                            ->options([
                                'pending' => 'Chờ duyệt',
                                'approved' => 'Đã duyệt',
                                'rejected' => 'Bị từ chối',
                            ])
                            ->required()
                            ->default('pending')
                            ->live(),
                        TextInput::make('rejection_reason')
                            ->label('Lý do từ chối')
                            ->visible(fn($get) => $get('status') === 'rejected')
                            ->requiredIf('status', 'rejected')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    // public static function infolist(Schema $schema): Schema
    // {
    //     return $schema
    //         ->components([
    //             Section::make('Thông tin công trình')
    //                 ->description('Thông tin định danh và liên hệ của công trình.')
    //                 ->icon('heroicon-o-document-text')
    //                 ->columnSpanFull()
    //                 ->schema([
    //                     TextEntry::make('dealer.name')
    //                         ->label('Đại lý thi công'),
    //                     TextEntry::make('systemType.name')
    //                         ->label('Hệ'),
    //                     TextEntry::make('title')
    //                         ->label('Tên công trình')
    //                         ->columnSpanFull(),
    //                     TextEntry::make('capacity')
    //                         ->label('Công suất'),
    //                     TextEntry::make('completion_date')
    //                         ->label('Thời gian hoàn thành')
    //                         ->date('d/m/Y'),
    //                     TextEntry::make('address')
    //                         ->label('Địa điểm thi công (Địa chỉ)')
    //                         ->columnSpanFull(),
    //                     TextEntry::make('description')
    //                         ->label('Mô tả')
    //                         ->columnSpanFull(),
    //                 ])
    //                 ->columns(2),

    //             Section::make('Hình ảnh')
    //                 ->description('Hình ảnh thi công thực tế')
    //                 ->icon('heroicon-o-photo')
    //                 ->columnSpanFull()
    //                 ->schema([
    //                     ViewEntry::make('images')
    //                         ->label('Ảnh công trình')
    //                         ->view('filament.infolists.entries.project-gallery')
    //                         ->columnSpanFull(),
    //                 ]),

    //             Section::make('Kiểm duyệt')
    //                 ->description('Trạng thái phê duyệt công trình')
    //                 ->icon('heroicon-o-shield-check')
    //                 ->columnSpanFull()
    //                 ->schema([
    //                     TextEntry::make('status')
    //                         ->label('Trạng thái')
    //                         ->badge()
    //                         ->formatStateUsing(fn (string $state): string => match ($state) {
    //                             'pending' => 'Chờ duyệt',
    //                             'approved' => 'Đã duyệt',
    //                             'rejected' => 'Bị từ chối',
    //                             default => $state,
    //                         })
    //                         ->color(fn (string $state): string => match ($state) {
    //                             'pending' => 'warning',
    //                             'approved' => 'success',
    //                             'rejected' => 'danger',
    //                             default => 'gray',
    //                         }),
    //                     TextEntry::make('rejection_reason')
    //                         ->label('Lý do từ chối')
    //                         ->visible(fn ($record) => $record?->status === 'rejected')
    //                         ->columnSpanFull(),
    //                 ])
    //                 ->columns(2),
    //         ]);
    // }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Thông tin công trình')
                    ->description('Thông tin định danh và liên hệ của công trình.')
                    ->icon('heroicon-o-document-text')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('dealer.name')
                            ->label('Đại lý thi công'),
                        TextEntry::make('systemType.name')
                            ->label('Hệ'),
                        TextEntry::make('title')
                            ->label('Tên công trình')
                            ->columnSpanFull(),
                        TextEntry::make('capacity')
                            ->label('Công suất'),
                        TextEntry::make('price')
                            ->label('Giá tiền')
                            ->formatStateUsing(fn ($state) => blank($state) ? 'Chưa cập nhật' : number_format((float) $state, 0, ',', '.') . ' VND'),
                        TextEntry::make('completion_date')
                            ->label('Thời gian hoàn thành')
                            ->date('d/m/Y'),
                        TextEntry::make('province.name')
                            ->label('Tỉnh/Thành')
                            ->placeholder('Chưa cập nhật'),
                        TextEntry::make('address')
                            ->label('Địa điểm thi công (Địa chỉ)')
                            ->columnSpanFull(),
                        TextEntry::make('description')
                            ->label('Mô tả')
                            ->placeholder('Chưa có mô tả')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Hình ảnh')
                    ->description('Hình ảnh thi công thực tế')
                    ->icon('heroicon-o-photo')
                    ->columnSpanFull()
                    ->schema([
                        ViewEntry::make('images')
                            ->label('Ảnh công trình')
                            ->view('filament.infolists.entries.project-gallery')
                            ->columnSpanFull(),
                    ]),

                Section::make('Kiểm duyệt')
                    ->description('Trạng thái phê duyệt công trình')
                    ->icon('heroicon-o-shield-check')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('status')
                            ->label('Trạng thái')
                            ->badge()
                            ->formatStateUsing(fn(string $state): string => match ($state) {
                                'pending' => 'Chờ duyệt',
                                'approved' => 'Đã duyệt',
                                'rejected' => 'Bị từ chối',
                                default => $state,
                            })
                            ->color(fn(string $state): string => match ($state) {
                                'pending' => 'warning',
                                'approved' => 'success',
                                'rejected' => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('rejection_reason')
                            ->label('Lý do từ chối')
                            ->visible(fn($record) => $record?->status === 'rejected')
                            ->placeholder('Không có')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('dealer.name')
                    ->label('Đại lý')
                    ->icon('heroicon-m-building-storefront')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),
                TextColumn::make('title')
                    ->label('Tên công trình')
                    ->searchable(),
                TextColumn::make('systemType.name')
                    ->label('Hệ')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('capacity')
                    ->label('Công suất')
                    ->searchable(),
                TextColumn::make('price')
                    ->label('Giá tiền')
                    ->formatStateUsing(fn ($state) => blank($state) ? '-' : number_format((float) $state, 0, ',', '.') . ' VND')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('province.name')
                    ->label('Tỉnh/Thành')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                // TextColumn::make('address')
                //     ->label('Địa điểm')
                //     ->searchable()
                //     ->toggleable(),
                TextColumn::make('completion_date')
                    ->label('Hoàn thành')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'pending' => 'Chờ duyệt',
                        'approved' => 'Đã duyệt',
                        'rejected' => 'Từ chối',
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                    }),
                TextColumn::make('created_at')
                    ->label('Ngày tạo')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('dealer_id')
                    ->relationship('dealer', 'name')
                    ->label('Đại lý'),
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Chờ duyệt',
                        'approved' => 'Đã duyệt',
                        'rejected' => 'Từ chối',
                    ])
                    ->label('Trạng thái'),
                TrashedFilter::make(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('approve')
                    ->label('Duyệt')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->visible(fn(Project $record) => $record->status === 'pending')
                    ->action(fn(Project $record) => $record->update(['status' => 'approved', 'approved_at' => now(), 'approved_by' => auth()->id()]))
                    ->requiresConfirmation(),
                Action::make('reject')
                    ->label('Từ chối')
                    ->icon('heroicon-m-x-mark')
                    ->color('danger')
                    ->visible(fn(Project $record) => $record->status === 'pending')
                    ->form([
                        TextInput::make('rejection_reason')
                            ->label('Lý do từ chối')
                            ->required(),
                    ])
                    ->action(fn(Project $record, array $data) => $record->update(['status' => 'rejected', 'rejection_reason' => $data['rejection_reason']])),
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
            'index' => ListProjects::route('/'),
            'create' => CreateProject::route('/create'),
            'view' => ViewProject::route('/{record}'),
            'edit' => EditProject::route('/{record}/edit'),
        ];
    }
}
