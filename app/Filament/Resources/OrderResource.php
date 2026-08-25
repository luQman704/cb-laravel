<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Order Status')->schema([
                Forms\Components\Select::make('status')
                    ->options([
                        'pending'    => 'Pending',
                        'processing' => 'Processing',
                        'shipped'    => 'Shipped',
                        'delivered'  => 'Delivered',
                        'cancelled'  => 'Cancelled',
                    ])
                    ->required(),

                Forms\Components\Select::make('payment_method')
                    ->options([
                        'paypal'    => 'PayPal',
                        'bank_wire' => 'Bank Wire',
                        'cod'       => 'Cash on Delivery',
                    ]),

                Forms\Components\TextInput::make('payment_reference')
                    ->maxLength(255),

                Forms\Components\Textarea::make('notes')
                    ->rows(2)
                    ->columnSpanFull(),
            ])->columns(3),

            Section::make('Shipping Address')->schema([
                Forms\Components\TextInput::make('ship_firstname')->required(),
                Forms\Components\TextInput::make('ship_lastname')->required(),
                Forms\Components\TextInput::make('ship_company'),
                Forms\Components\TextInput::make('ship_address1')->required()->columnSpanFull(),
                Forms\Components\TextInput::make('ship_address2')->columnSpanFull(),
                Forms\Components\TextInput::make('ship_city')->required(),
                Forms\Components\TextInput::make('ship_postcode')->required(),
                Forms\Components\TextInput::make('ship_country')->required()->default('ZA'),
                Forms\Components\TextInput::make('ship_phone'),
            ])->columns(3),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Order Summary')->schema([
                Infolists\Components\TextEntry::make('id')->label('Order ID'),
                Infolists\Components\TextEntry::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'pending'    => 'warning',
                        'processing' => 'info',
                        'shipped'    => 'primary',
                        'delivered'  => 'success',
                        'cancelled'  => 'danger',
                        default      => 'gray',
                    }),
                Infolists\Components\TextEntry::make('created_at')->dateTime(),
                Infolists\Components\TextEntry::make('payment_method'),
                Infolists\Components\TextEntry::make('payment_reference'),
                Infolists\Components\TextEntry::make('subtotal')->money('ZAR'),
                Infolists\Components\TextEntry::make('shipping_cost')->money('ZAR'),
                Infolists\Components\TextEntry::make('tax_amount')->money('ZAR'),
                Infolists\Components\TextEntry::make('total')->money('ZAR'),
            ])->columns(3),

            Section::make('Customer')->schema([
                Infolists\Components\TextEntry::make('customer.email')->label('Account email')->default('Guest'),
                Infolists\Components\TextEntry::make('guest_email')->label('Guest email'),
                Infolists\Components\TextEntry::make('shipping_method_name')->label('Shipping method'),
            ])->columns(3),

            Section::make('Shipping Address')->schema([
                Infolists\Components\TextEntry::make('ship_firstname')->label('First name'),
                Infolists\Components\TextEntry::make('ship_lastname')->label('Last name'),
                Infolists\Components\TextEntry::make('ship_company')->label('Company'),
                Infolists\Components\TextEntry::make('ship_address1')->label('Address 1'),
                Infolists\Components\TextEntry::make('ship_address2')->label('Address 2'),
                Infolists\Components\TextEntry::make('ship_city')->label('City'),
                Infolists\Components\TextEntry::make('ship_postcode')->label('Postcode'),
                Infolists\Components\TextEntry::make('ship_country')->label('Country'),
                Infolists\Components\TextEntry::make('ship_phone')->label('Phone'),
            ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable()
                    ->width(60),

                Tables\Columns\TextColumn::make('customer.email')
                    ->label('Customer')
                    ->default(fn ($record) => $record->guest_email ?: 'Guest')
                    ->searchable(),

                Tables\Columns\TextColumn::make('ship_firstname')
                    ->label('Ship to')
                    ->formatStateUsing(fn ($state, $record) => "{$record->ship_firstname} {$record->ship_lastname}"),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'pending'    => 'warning',
                        'processing' => 'info',
                        'shipped'    => 'primary',
                        'delivered'  => 'success',
                        'cancelled'  => 'danger',
                        default      => 'gray',
                    }),

                Tables\Columns\TextColumn::make('total')
                    ->money('ZAR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_method')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending'    => 'Pending',
                        'processing' => 'Processing',
                        'shipped'    => 'Shipped',
                        'delivered'  => 'Delivered',
                        'cancelled'  => 'Cancelled',
                    ]),
            ])
            ->actions([
                Actions\ViewAction::make(),
                Actions\Action::make('update_status')
                    ->label('Status')
                    ->icon('heroicon-o-arrow-path')
                    ->form([
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending'    => 'Pending',
                                'processing' => 'Processing',
                                'shipped'    => 'Shipped',
                                'delivered'  => 'Delivered',
                                'cancelled'  => 'Cancelled',
                            ])
                            ->required(),
                    ])
                    ->action(fn (Order $record, array $data) => $record->update(['status' => $data['status']]))
                    ->successNotificationTitle('Order status updated'),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getRelationManagers(): array
    {
        return [
            RelationManagers\OrderItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'view'  => Pages\ViewOrder::route('/{record}'),
            'edit'  => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
