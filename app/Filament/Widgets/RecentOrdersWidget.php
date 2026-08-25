<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentOrdersWidget extends TableWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Recent Orders')
            ->query(
                Order::query()->latest()->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Order')
                    ->formatStateUsing(fn ($state) => "#{$state}")
                    ->width(70),

                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Customer')
                    ->getStateUsing(fn (Order $record) =>
                        $record->customer
                            ? "{$record->customer->firstname} {$record->customer->lastname}"
                            : ($record->guest_email ?: 'Guest')
                    )
                    ->searchable(false),

                Tables\Columns\TextColumn::make('ship_city')
                    ->label('City'),

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

                Tables\Columns\TextColumn::make('payment_method')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('total')
                    ->money('ZAR'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->actions([
                Actions\Action::make('view')
                    ->url(fn (Order $record) => route('filament.admin.resources.orders.view', $record))
                    ->icon('heroicon-m-eye')
                    ->label(''),
            ])
            ->paginated(false)
            ->emptyStateHeading('No orders yet')
            ->emptyStateDescription('Orders placed through the storefront will appear here.')
            ->emptyStateIcon('heroicon-o-shopping-cart');
    }
}
