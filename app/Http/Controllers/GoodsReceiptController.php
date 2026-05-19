<?php

namespace App\Http\Controllers;

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptDetail;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class GoodsReceiptController extends Controller
{
    public function index()
    {
        $receipts = GoodsReceipt::with(['supplier:id,name', 'user:id,name'])
            ->latest()
            ->paginate(15);

        return Inertia::render('GoodsReceipts/Index', [
            'receipts' => $receipts,
        ]);
    }

    public function create()
    {
        return Inertia::render('GoodsReceipts/Create', [
            'suppliers' => Supplier::select('id', 'name')->get(),
            'products'  => Product::select('id', 'name', 'barcode', 'cost_price', 'selling_price', 'stock', 'unit')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'supplier_id'  => ['required', 'exists:suppliers,id'],
            'receipt_date' => ['required', 'date'],
            'reference_no' => ['nullable', 'string', 'max:150'],
            'note'         => ['nullable', 'string'],
            'items'        => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.qty'        => ['required', 'integer', 'min:1'],
            'items.*.cost_price' => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($request) {
            // Generate receipt number (e.g. GR-20260519-0001)
            $date = date('Ymd');
            $todayCount = GoodsReceipt::whereDate('created_at', today())->count();
            $seq = str_pad($todayCount + 1, 4, '0', STR_PAD_LEFT);
            $receiptNo = "GR-{$date}-{$seq}";

            $totalCost = collect($request->items)->sum(function ($item) {
                return $item['qty'] * $item['cost_price'];
            });

            // 1. Create Goods Receipt
            $receipt = GoodsReceipt::create([
                'receipt_no'   => $receiptNo,
                'supplier_id'  => $request->supplier_id,
                'user_id'      => Auth::id(),
                'receipt_date' => $request->receipt_date,
                'reference_no' => $request->reference_no,
                'total_cost'   => $totalCost,
                'note'         => $request->note,
            ]);

            // 2. Loop Items
            foreach ($request->items as $item) {
                $product = Product::lockForUpdate()->find($item['product_id']);
                $oldStock = $product->stock;
                $newStock = $oldStock + $item['qty'];
                $subtotal = $item['qty'] * $item['cost_price'];

                // Create Detail
                GoodsReceiptDetail::create([
                    'goods_receipt_id' => $receipt->id,
                    'product_id'       => $product->id,
                    'qty'              => $item['qty'],
                    'cost_price'       => $item['cost_price'],
                    'subtotal'         => $subtotal,
                ]);

                // Update Product Stock & Cost Price
                $product->update([
                    'stock'      => $newStock,
                    'cost_price' => $item['cost_price'], // update cost price dynamically to the newest purchase price
                ]);

                // Write Stock Movement Ledger
                StockMovement::create([
                    'product_id'     => $product->id,
                    'user_id'        => Auth::id(),
                    'type'           => 'in',
                    'quantity'       => $item['qty'],
                    'old_stock'      => $oldStock,
                    'new_stock'      => $newStock,
                    'reference_type' => GoodsReceipt::class,
                    'reference_id'   => $receipt->id,
                    'note'           => "Penerimaan Kulakan Barang via No. Penerimaan {$receiptNo}.",
                ]);
            }
        });

        return redirect()->route('goods-receipts.index')->with('success', 'Penerimaan barang berhasil disimpan.');
    }

    public function show(GoodsReceipt $goodsReceipt)
    {
        $goodsReceipt->load(['supplier:id,name,phone,address', 'user:id,name', 'details.product:id,name,barcode,unit']);
        return Inertia::render('GoodsReceipts/Show', [
            'receipt' => $goodsReceipt,
        ]);
    }
}
