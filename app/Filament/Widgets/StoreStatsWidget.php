<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StoreStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalOrders   = Order::count();
        $totalRevenue  = Order::whereNotIn('status', ['cancelled'])->sum('total');
        $totalCustomers = Customer::where('active', true)->count();
        $totalProducts  = Product::where('active', true)->count();

        $ordersThisMonth = Order::where('created_at', '>=', now()->startOfMonth())->count();
        $revenueThisMonth = Order::where('created_at', '>=', now()->startOfMonth())
            ->whereNotIn('status', ['cancelled'])
            ->sum('total');

        $newCustomersThisMonth = Customer::where('created_at', '>=', now()->startOfMonth())->count();

        $lowStockCount = \App\Models\StockAvailability::where('quantity', '<=', 5)->count();

        return [
            Stat::make('Total Orders', $totalOrders)
                ->description("{$ordersThisMonth} this month")
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('primary')
                ->icon('heroicon-o-shopping-cart'),

            Stat::make('Revenue', 'R ' . number_format((float) $totalRevenue, 2))
                ->description('R ' . number_format((float) $revenueThisMonth, 2) . ' this month')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success')
                ->icon('heroicon-o-banknotes'),

            Stat::make('Active Customers', $totalCustomers)
                ->description("{$newCustomersThisMonth} joined this month")
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info')
                ->icon('heroicon-o-users'),

            Stat::make('Active Products', $totalProducts)
                ->description($lowStockCount > 0 ? "{$lowStockCount} low or out of stock" : 'All well stocked')
                ->descriptionIcon($lowStockCount > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($lowStockCount > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-tag'),
        ];
    }
}
