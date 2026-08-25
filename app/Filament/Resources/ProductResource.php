<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Basic Information')->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, Forms\Set $set) => $set('slug', Str::slug($state))),

                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),

                Forms\Components\TextInput::make('reference')
                    ->label('SKU / Reference')
                    ->maxLength(100)
                    ->unique(ignoreRecord: true),

                Forms\Components\TextInput::make('price')
                    ->label('Price (ex VAT)')
                    ->required()
                    ->numeric()
                    ->prefix('R')
                    ->helperText('Excluding 15% VAT. Customer price = this × 1.15'),

                Forms\Components\TextInput::make('weight')
                    ->numeric()
                    ->suffix('kg'),

                Forms\Components\Toggle::make('active')
                    ->label('Active / Visible')
                    ->default(true),
            ])->columns(2),

            Section::make('Description')->schema([
                Forms\Components\Textarea::make('short_description')
                    ->label('Short description')
                    ->rows(3)
                    ->columnSpanFull(),

                Forms\Components\RichEditor::make('description')
                    ->label('Full description')
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable()
                    ->width(60),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('reference')
                    ->label('SKU')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('price')
                    ->label('Price ex VAT')
                    ->money('ZAR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('price_inc_tax')
                    ->label('Incl. VAT')
                    ->getStateUsing(fn (Product $record) => round((float) $record->price * 1.15, 2))
                    ->money('ZAR'),

                Tables\Columns\TextColumn::make('stock.quantity')
                    ->label('Stock')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state === null => 'gray',
                        $state <= 0    => 'danger',
                        $state <= 5    => 'warning',
                        default        => 'success',
                    }),

                Tables\Columns\IconColumn::make('active')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('active')
                    ->label('Active status'),

                Tables\Filters\Filter::make('low_stock')
                    ->label('Low / no stock')
                    ->query(fn ($query) => $query->whereHas('stock', fn ($q) => $q->where('quantity', '<=', 5))),
            ])
            ->actions([
                Actions\EditAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('id');
    }

    public static function getRelationManagers(): array
    {
        return [
            RelationManagers\StockRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit'   => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
