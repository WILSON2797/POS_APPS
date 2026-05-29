<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();

        // 1. Stats Today
        $salesTodayCount = Transaction::whereDate('created_at', $today)->count();
        $revenueToday = Transaction::whereDate('created_at', $today)->sum('grand_total');

        // 2. Stock Stats
        $totalProducts = Product::where('is_active', true)->count();
        $lowStockCount = Product::where('is_active', true)
            ->whereColumn('stock', '<=', 'min_stock')
            ->count();

        // 3. Recent Transactions (last 5)
        $recentTransactions = Transaction::with('customer')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn($tx) => [
                'id' => $tx->id,
                'invoice_no' => $tx->invoice_no,
                'customer' => $tx->customer?->name ?? 'Umum',
                'grand_total' => $tx->grand_total,
                'payment_method' => $tx->payment_method,
                'time' => $tx->created_at->diffForHumans(),
            ]);

        // 4. Top Selling Products (last 30 days)
        $topProducts = TransactionDetail::select('product_id', DB::raw('SUM(qty) as total_sold'))
            ->whereHas('transaction', function($q) {
                $q->where('created_at', '>=', Carbon::now()->subDays(30));
            })
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->limit(5)
            ->with('product:id,name,selling_price')
            ->get()
            ->map(fn($td) => [
                'name' => $td->product->name ?? 'Produk Dihapus',
                'selling_price' => $td->product->selling_price ?? 0,
                'total_sold' => (int)$td->total_sold,
            ]);

        return Inertia::render('Dashboard/Index', [
            'stats' => [
                'sales_today_count' => $salesTodayCount,
                'revenue_today' => (float)$revenueToday,
                'total_products' => $totalProducts,
                'low_stock_count' => $lowStockCount,
            ],
            'recent_transactions' => $recentTransactions,
            'top_products' => $topProducts,
        ]);
    }
}
