<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Actions;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class StockRelationManager extends RelationManager
{
    protected static string $relationship = 'stock';

    protected static ?string $title = 'Stock';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('quantity')
                ->required()
                ->integer()
                ->minValue(0),

            Forms\Components\Toggle::make('allow_out_of_stock')
                ->label('Allow purchase when out of stock'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('quantity')
                    ->badge()
                    ->color(fn ($state) => $state <= 0 ? 'danger' : ($state <= 5 ? 'warning' : 'success')),

                Tables\Columns\IconColumn::make('allow_out_of_stock')
                    ->label('Allow out-of-stock orders')
                    ->boolean(),
            ])
            ->headerActions([
                Actions\CreateAction::make(),
            ])
            ->actions([
                Actions\EditAction::make(),
            ]);
    }
}
