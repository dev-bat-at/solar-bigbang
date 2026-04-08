<?php

namespace App\Filament\Resources\Leads;

use App\Models\Lead;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Support\RawJs;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\Leads\Pages\ListLeads;
use App\Filament\Resources\Leads\Pages\CreateLead;
use App\Filament\Resources\Leads\Pages\EditLead;

class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-funnel';

    protected static ?string $navigationLabel = 'Tiềm năng (Lead)';

    protected static ?string $modelLabel = 'Lead';

    protected static ?string $pluralModelLabel = 'Danh sách Lead';

    protected static string | \UnitEnum | null $navigationGroup = 'Quản lý Đối tác & Lead';

    protected static ?int $navigationSort = 4;

    protected static function moneyMask(): RawJs
    {
        return RawJs::make('$money($input, \',\', \'.\', 0)');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return ! in_array(static::class, config('admin_menu.hidden_resources', []));
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Thông tin Lead')
                    ->schema([
                        TextInput::make('code')
                            ->label('Mã Lead')
                            ->default(fn () => 'L' . date('Ymd') . strtoupper(\Illuminate\Support\Str::random(4)))
                            ->required()
                            ->unique(ignoreRecord: true),
                        Select::make('customer_id')
                            ->label('Khách hàng')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('dealer_id')
                            ->label('Đại lý xử lý')
                            ->relationship('dealer', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('status')
                            ->label('Trạng thái')
                            ->options([
                                'new' => 'Mới',
                                'assigned' => 'Đã gán đại lý',
                                'contacting' => 'Đang liên hệ',
                                'quoted' => 'Đã báo giá',
                                'negotiating' => 'Đang thương thảo',
                                'won' => 'Chốt thành công',
                                'lost' => 'Thất bại',
                                'expired' => 'Hết hạn',
                                'reopened' => 'Mở lại',
                            ])
                            ->required()
                            ->default('new'),
                        TextInput::make('source')
                            ->label('Nguồn Lead')
                            ->maxLength(255),
                        TextInput::make('province_name')
                            ->label('Tỉnh/Thành')
                            ->maxLength(255),
                        TextInput::make('estimated_value')
                            ->label('Giá trị dự kiến')
                            ->mask(static::moneyMask())
                            ->stripCharacters('.')
                            ->numeric()
                            ->prefix('VNĐ'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Mã Lead')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->label('Khách hàng')
                    ->searchable(),
                TextColumn::make('dealer.name')
                    ->label('Đại lý')
                    ->placeholder('Chưa gán')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Trạng thái')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'new' => 'info',
                        'won' => 'success',
                        'lost' => 'danger',
                        'expired' => 'gray',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'new' => 'Mới',
                        'assigned' => 'Đã gán',
                        'contacting' => 'Đang liên hệ',
                        'quoted' => 'Báo giá',
                        'won' => 'Thành công',
                        'lost' => 'Thất bại',
                        default => $state,
                    }),
                TextColumn::make('province_name')
                    ->label('Khu vực')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Ngày tạo')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'new' => 'Mới',
                        'won' => 'Thành công',
                        'lost' => 'Thất bại',
                    ]),
            ])
            ->actions([
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

    public static function getPages(): array
    {
        return [
            'index' => ListLeads::route('/'),
            'create' => CreateLead::route('/create'),
            'edit' => EditLead::route('/{record}/edit'),
        ];
    }
}
