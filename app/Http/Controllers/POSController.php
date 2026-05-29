<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class POSController extends Controller
{
    public function index()
    {
        return Inertia::render('POS/Index', [
            'products'   => Product::with('category')->where('is_active', true)->where('stock', '>', 0)->get(),
            'categories' => Category::select('id', 'name')->get(),
            'customers'  => Customer::select('id', 'name', 'phone', 'points', 'level')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product.id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.note' => 'nullable|string',
            'customer_id' => 'nullable|exists:customers,id',
            'discount_type' => 'required|in:flat,percent',
            'discount_value' => 'nullable|numeric|min:0',
            'tax_rate' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,transfer,qris,e-wallet',
            'amount_paid' => 'required|numeric|min:0',
            'note' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request) {
            $items = $request->input('items');
            $customerId = $request->input('customer_id');

            // 1. Calculate and validate items
            $subtotal = 0;
            $itemsData = [];

            foreach ($items as $item) {
                $product = Product::lockForUpdate()->findOrFail($item['product']['id']);
                
                // Stock validation
                if ($product->stock < $item['qty']) {
                    return back()->withErrors(['stock' => "Stok produk {$product->name} tidak mencukupi (Tersisa {$product->stock})."]);
                }

                $itemDiscount = (float)($item['discount'] ?? 0);
                $itemSubtotal = ($product->selling_price * $item['qty']) - $itemDiscount;
                $subtotal += $itemSubtotal;

                $itemsData[] = [
                    'product' => $product,
                    'qty' => $item['qty'],
                    'cost_price' => $product->cost_price,
                    'selling_price' => $product->selling_price,
                    'discount' => $itemDiscount,
                    'subtotal' => $itemSubtotal,
                ];
            }

            // 2. Calculate final prices
            $discountValue = (float)($request->input('discount_value') ?? 0);
            $discountType = $request->input('discount_type');
            $transactionDiscount = 0;

            if ($discountType === 'percent') {
                $transactionDiscount = ($subtotal * $discountValue) / 100;
            } else {
                $transactionDiscount = $discountValue;
            }

            $taxRate = (float)($request->input('tax_rate') ?? 0);
            $subtotalAfterDiscount = max(0, $subtotal - $transactionDiscount);
            $taxAmount = ($subtotalAfterDiscount * $taxRate) / 100;
            $grandTotal = round($subtotalAfterDiscount + $taxAmount);

            $amountPaid = (float)$request->input('amount_paid');
            $change = 0;
            if ($request->input('payment_method') === 'cash') {
                if ($amountPaid < $grandTotal) {
                    return back()->withErrors(['amount_paid' => "Jumlah pembayaran kurang dari total belanja."]);
                }
                $change = $amountPaid - $grandTotal;
            } else {
                $amountPaid = $grandTotal; // non-cash automatically exact amount
            }

            // 3. Create Transaction
            $transaction = Transaction::create([
                'invoice_no' => Transaction::generateInvoiceNo(),
                'user_id' => Auth::id(),
                'customer_id' => $customerId,
                'subtotal' => $subtotal,
                'discount' => $transactionDiscount,
                'tax' => $taxAmount,
                'grand_total' => $grandTotal,
                'amount_paid' => $amountPaid,
                'change' => $change,
                'payment_method' => $request->input('payment_method'),
                'note' => $request->input('note'),
            ]);

            // 4. Create details, deduct stock
            foreach ($itemsData as $data) {
                TransactionDetail::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $data['product']->id,
                    'qty' => $data['qty'],
                    'cost_price' => $data['cost_price'],
                    'selling_price' => $data['selling_price'],
                    'discount' => $data['discount'],
                    'subtotal' => $data['subtotal'],
                ]);

                // Ambil sisa stok saat ini sebelum dikurangi (Locked Row)
                $oldStock = $data['product']->stock;
                $newStock = $oldStock - $data['qty'];

                // Potong stok produk
                $data['product']->decrement('stock', $data['qty']);

                // Catat mutasi stok keluar (Stock Outbound) secara otomatis
                StockMovement::create([
                    'product_id' => $data['product']->id,
                    'user_id' => Auth::id(),
                    'type' => 'out',
                    'quantity' => $data['qty'],
                    'old_stock' => $oldStock,
                    'new_stock' => $newStock,
                    'reference_type' => Transaction::class,
                    'reference_id' => $transaction->id,
                    'note' => "Penjualan POS via Invoice #{$transaction->invoice_no}",
                ]);
            }

            // 5. Customer Loyalty Points Calculation (1 point per Rp 10.000 spent)
            if ($customerId) {
                $customer = Customer::findOrFail($customerId);
                $earnedPoints = floor($grandTotal / 10000);
                
                if ($earnedPoints > 0) {
                    $customer->increment('points', $earnedPoints);
                    
                    // Automatically upgrade level based on points
                    $currentPoints = $customer->points;
                    $newLevel = 'regular';
                    if ($currentPoints >= 1000) {
                        $newLevel = 'platinum';
                    } elseif ($currentPoints >= 500) {
                        $newLevel = 'gold';
                    } elseif ($currentPoints >= 200) {
                        $newLevel = 'silver';
                    }
                    
                    if ($customer->level !== $newLevel) {
                        $customer->update(['level' => $newLevel]);
                    }
                }
            }

            return redirect()->back()->with([
                'success' => 'Transaksi berhasil diproses!',
                'receipt' => [
                    'id' => $transaction->id,
                    'invoice_no' => $transaction->invoice_no,
                    'created_at' => $transaction->created_at->format('d/m/Y H:i:s'),
                    'cashier' => Auth::user()->name,
                    'customer' => $transaction->customer?->name ?? 'Umum',
                    'items' => collect($itemsData)->map(fn($d) => [
                        'name' => $d['product']->name,
                        'qty' => $d['qty'],
                        'price' => $d['selling_price'],
                        'discount' => $d['discount'],
                        'subtotal' => $d['subtotal'],
                    ])->toArray(),
                    'subtotal' => $transaction->subtotal,
                    'discount' => $transaction->discount,
                    'tax' => $transaction->tax,
                    'grand_total' => $transaction->grand_total,
                    'amount_paid' => $transaction->amount_paid,
                    'change' => $transaction->change,
                    'payment_method' => strtoupper($transaction->payment_method),
                    'note' => $transaction->note,
                ]
            ]);
        });
    }

    public function salesReport(Request $request)
    {
        $query = Transaction::with(['customer', 'user', 'details']);

        // Filter: Rentang Tanggal
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                $request->input('start_date') . ' 00:00:00',
                $request->input('end_date') . ' 23:59:59'
            ]);
        }

        // Filter: Metode Pembayaran
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->input('payment_method'));
        }

        $transactions = $query->latest()->get();

        $totalRevenue = 0;
        $totalTax = 0;
        $totalDiscount = 0;
        $totalCost = 0;
        $paymentStats = [
            'cash' => 0,
            'transfer' => 0,
            'qris' => 0,
            'e-wallet' => 0,
        ];

        foreach ($transactions as $tx) {
            $totalRevenue += $tx->grand_total;
            $totalTax += $tx->tax;
            $totalDiscount += $tx->discount;
            
            if (isset($paymentStats[$tx->payment_method])) {
                $paymentStats[$tx->payment_method] += $tx->grand_total;
            }
            
            foreach ($tx->details as $detail) {
                $totalCost += $detail->cost_price * $detail->qty;
            }
        }

        $netRevenue = 0;
        foreach ($transactions as $tx) {
            $netRevenue += ($tx->subtotal - $tx->discount);
        }
        $totalProfit = $netRevenue - $totalCost;

        return Inertia::render('Reports/Sales', [
            'transactions' => $transactions->map(function ($tx) {
                $txCost = $tx->details->sum(fn($d) => $d->cost_price * $d->qty);
                $txProfit = ($tx->subtotal - $tx->discount) - $txCost;
                return [
                    'id' => $tx->id,
                    'invoice_no' => $tx->invoice_no,
                    'created_at' => $tx->created_at->format('d/m/Y H:i'),
                    'customer' => $tx->customer?->name ?? 'Umum',
                    'cashier' => $tx->user->name,
                    'subtotal' => $tx->subtotal,
                    'discount' => $tx->discount,
                    'tax' => $tx->tax,
                    'grand_total' => $tx->grand_total,
                    'payment_method' => $tx->payment_method,
                    'estimated_profit' => $txProfit,
                ];
            }),
            'summary' => [
                'total_sales' => $transactions->count(),
                'total_revenue' => $totalRevenue,
                'total_tax' => $totalTax,
                'total_discount' => $totalDiscount,
                'total_profit' => $totalProfit,
                'payment_stats' => $paymentStats,
            ],
            'filters' => $request->only(['start_date', 'end_date', 'payment_method'])
        ]);
    }

    public function exportSalesReport(Request $request)
    {
        $query = Transaction::with(['customer', 'user', 'details']);

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                $request->input('start_date') . ' 00:00:00',
                $request->input('end_date') . ' 23:59:59'
            ]);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->input('payment_method'));
        }

        $transactions = $query->latest()->get();

        // Ambil nama toko
        $settingsPath = storage_path('app/settings.json');
        $brandName = config('app.name', 'POS Apps');
        if (file_exists($settingsPath)) {
            $settings = json_decode(file_get_contents($settingsPath), true);
            $brandName = $settings['brand_name'] ?? $brandName;
        }

        $periode = 'Semua Periode';
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $periode = 'Periode: ' . date('d/m/Y', strtotime($request->input('start_date'))) . ' s/d ' . date('d/m/Y', strtotime($request->input('end_date')));
        }

        $export = new \App\Exports\SalesReportExport($transactions, $brandName, $periode);
        return $export->download();
    }
}
