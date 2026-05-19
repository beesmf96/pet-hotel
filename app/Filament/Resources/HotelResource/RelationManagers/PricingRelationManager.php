<?php

namespace App\Filament\Resources\HotelResource\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PricingRelationManager extends RelationManager
{
    protected static string $relationship = 'pricing';

    protected static ?string $title = 'Pricing';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Select::make('pet_type')
                ->options([
                    'dog' => 'Dog',
                    'cat' => 'Cat',
                    'rabbit' => 'Rabbit',
                    'bird' => 'Bird',
                    'other' => 'Other',
                ])
                ->required(),
            Forms\Components\TextInput::make('price_per_night')
                ->numeric()
                ->prefix('RM')
                ->required()
                ->minValue(0),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('pet_type')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => ucfirst($state)),
                TextColumn::make('price_per_night')
                    ->money('MYR')
                    ->label('Price / Night'),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
