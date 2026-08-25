<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LowStockWidget extends TableWidget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Stock Alerts')
            ->description('Products with 5 or fewer units remaining')
            ->query(
                Product::query()
                    ->where('active', true)
                    ->whereHas('stock', fn ($q) => $q->where('quantity', '<=', 5))
                    ->with(['stock'])
                    ->orderBy(
                        \App\Models\StockAvailability::select('quantity')
                            ->whereColumn('product_id', 'products.id'),
                        'asc'
                    )
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->limit(50),

                Tables\Columns\TextColumn::make('reference')
                    ->label('SKU')
                    ->copyable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('stock.quantity')
                    ->label('Stock')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state <= 0 => 'danger',
                        $state <= 2 => 'danger',
                        $state <= 5 => 'warning',
                        default     => 'success',
                    })
                    ->formatStateUsing(fn ($state) => $state <= 0 ? 'OUT' : $state),

                Tables\Columns\TextColumn::make('price')
                    ->label('Price ex VAT')
                    ->money('ZAR'),

                Tables\Columns\TextColumn::make('stock.allow_out_of_stock')
                    ->label('Allows Backorder')
                    ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No')
                    ->badge()
                    ->color(fn ($state) => $state ? 'info' : 'gray'),
            ])
            ->actions([
                Actions\Action::make('edit')
                    ->url(fn (Product $record) => route('filament.admin.resources.products.edit', $record))
                    ->icon('heroicon-m-pencil-square')
                    ->label('Update Stock'),
            ])
            ->paginated(false)
            ->emptyStateHeading('All stocked up')
            ->emptyStateDescription('No products are running low on stock.')
            ->emptyStateIcon('heroicon-o-check-circle');
    }
}
