<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HotelResource\Pages;
use App\Filament\Resources\HotelResource\RelationManagers;
use App\Models\PetHotel;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class HotelResource extends Resource
{
    protected static ?string $model = PetHotel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static ?string $navigationLabel = 'Hotels';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Basic Info')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state ?? '')))
                        ->columnSpan(1),
                    Forms\Components\TextInput::make('slug')
                        ->required()
                        ->unique(PetHotel::class, 'slug', ignoreRecord: true)
                        ->columnSpan(1),
                    Forms\Components\Textarea::make('description')
                        ->rows(3)
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('address')
                        ->required()
                        ->columnSpan(2),
                    Forms\Components\TextInput::make('city')
                        ->required()
                        ->columnSpan(1),
                    Forms\Components\Toggle::make('is_active')
                        ->default(true)
                        ->columnSpan(1),
                    Forms\Components\TextInput::make('lat')
                        ->numeric()
                        ->nullable()
                        ->label('Latitude')
                        ->columnSpan(1),
                    Forms\Components\TextInput::make('lng')
                        ->numeric()
                        ->nullable()
                        ->label('Longitude')
                        ->columnSpan(1),
                    Forms\Components\FileUpload::make('cover_photo')
                        ->image()
                        ->disk(config('filesystems.photos'))
                        ->visibility('public')
                        ->nullable()
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Section::make('Check-in / Check-out Policy')
                ->relationship('policy')
                ->schema([
                    Forms\Components\TimePicker::make('check_in_time')
                        ->required()
                        ->columnSpan(1),
                    Forms\Components\TimePicker::make('check_out_time')
                        ->required()
                        ->columnSpan(1),
                    Forms\Components\Textarea::make('cancellation_policy')
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('city')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
                TextColumn::make('created_at')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Active'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PhotosRelationManager::class,
            RelationManagers\PricingRelationManager::class,
            RelationManagers\OwnersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHotels::route('/'),
            'create' => Pages\CreateHotel::route('/create'),
            'edit' => Pages\EditHotel::route('/{record}/edit'),
        ];
    }
}
