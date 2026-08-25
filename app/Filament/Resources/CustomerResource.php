<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Filament\Resources\CustomerResource\RelationManagers;
use App\Models\Customer;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-users';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make()->schema([
                Forms\Components\TextInput::make('firstname')->required(),
                Forms\Components\TextInput::make('lastname')->required(),
                Forms\Components\TextInput::make('email')->email()->required()->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('phone'),
                Forms\Components\DatePicker::make('birthday'),
                Forms\Components\Toggle::make('active')->default(true),
                Forms\Components\Toggle::make('newsletter')->label('Newsletter subscriber'),
            ])->columns(2),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make()->schema([
                Infolists\Components\TextEntry::make('id'),
                Infolists\Components\TextEntry::make('firstname')->label('First name'),
                Infolists\Components\TextEntry::make('lastname')->label('Last name'),
                Infolists\Components\TextEntry::make('email'),
                Infolists\Components\TextEntry::make('phone'),
                Infolists\Components\TextEntry::make('birthday')->date(),
                Infolists\Components\IconEntry::make('active')->boolean(),
                Infolists\Components\IconEntry::make('newsletter')->boolean()->label('Newsletter'),
                Infolists\Components\TextEntry::make('created_at')->dateTime()->label('Registered'),
            ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable()->width(60),

                Tables\Columns\TextColumn::make('firstname')
                    ->label('Name')
                    ->formatStateUsing(fn ($state, $record) => "{$record->firstname} {$record->lastname}")
                    ->searchable(['firstname', 'lastname']),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('phone')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('orders_count')
                    ->label('Orders')
                    ->counts('orders')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\IconColumn::make('newsletter')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('active')->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('active'),
                Tables\Filters\TernaryFilter::make('newsletter'),
            ])
            ->actions([
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getRelationManagers(): array
    {
        return [
            RelationManagers\OrdersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'view'  => Pages\ViewCustomer::route('/{record}'),
            'edit'  => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
