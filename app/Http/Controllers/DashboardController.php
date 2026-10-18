<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\StockMovement;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();
        $sales = Sale::query()->where('status', 'completed')->whereBetween('created_at', [$start, $end]);
        $purchases = Purchase::query()->where('status', 'received')->whereBetween('received_at', [$start, $end]);

        $dailySales = Sale::query()
            ->where('status', 'completed')
            ->whereBetween('created_at', [now()->subDays(13)->startOfDay(), now()->endOfDay()])
            ->selectRaw('DATE(created_at) as day, SUM(total) as amount')
            ->groupBy('day')
            ->pluck('amount', 'day');

        $days = collect(range(13, 0))->map(fn (int $offset) => now()->subDays($offset)->toDateString());

        return view('dashboard', [
            'activeProducts' => Product::where('is_active', true)->count(),
            'lowStockCount' => Product::where('is_active', true)->lowStock()->count(),
            'monthlySales' => $sales->sum('total'),
            'monthlyPurchases' => $purchases->sum('total'),
            'salesLabels' => $days->map(fn (string $day) => Carbon::parse($day)->format('d/m'))->values(),
            'salesAmounts' => $days->map(fn (string $day) => (float) ($dailySales[$day] ?? 0))->values(),
            'topProducts' => \App\Models\SaleItem::query()
                ->selectRaw('product_id, product_name, sku, SUM(quantity) as units, SUM(line_total) as revenue')
                ->whereHas('sale', fn ($query) => $query->where('status', 'completed')->whereBetween('created_at', [$start, $end]))
                ->groupBy('product_id', 'product_name', 'sku')
                ->orderByDesc('units')
                ->limit(5)
                ->get(),
            'recentSales' => Sale::with('customer')->where('status', 'completed')->latest()->limit(5)->get(),
            'recentMovements' => StockMovement::with('product', 'user')->latest()->limit(6)->get(),
        ]);
    }
}
