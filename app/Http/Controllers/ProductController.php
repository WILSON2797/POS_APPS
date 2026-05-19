<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Supplier;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function index()
    {
        return Inertia::render('Products/Index', [
            'products'   => Product::with('category:id,name')->latest()->get(),
            'categories' => Category::select('id', 'name')->get(),
        ]);
    }

    public function store(StoreProductRequest $request)
    {
        $data = $request->validated();
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }
        
        $product = Product::create($data);
        
        // Catat stok awal pendaftaran jika produk baru memiliki stok awal lebih besar dari 0
        if ($product->stock > 0) {
            StockMovement::create([
                'product_id' => $product->id,
                'user_id' => Auth::id(),
                'type' => 'in',
                'quantity' => $product->stock,
                'old_stock' => 0,
                'new_stock' => $product->stock,
                'reference_type' => Product::class,
                'reference_id' => $product->id,
                'note' => "Stok awal saat pendaftaran produk baru.",
            ]);
        }
        
        return back()->with('success', 'Produk berhasil ditambahkan.');
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $data = $request->validated();
        $oldStock = $product->stock;
        
        if ($request->hasFile('image')) {
            if ($product->image) Storage::disk('public')->delete($product->image);
            $data['image'] = $request->file('image')->store('products', 'public');
        }
        
        $product->update($data);
        
        // Periksa apakah ada perubahan stok manual oleh admin
        $newStock = $product->fresh()->stock;
        if ($oldStock !== $newStock) {
            $diff = abs($newStock - $oldStock);
            $type = $newStock > $oldStock ? 'in' : 'out';
            $note = $newStock > $oldStock 
                ? "Penambahan/koreksi stok manual oleh admin."
                : "Pengurangan/koreksi stok manual oleh admin.";
                
            StockMovement::create([
                'product_id' => $product->id,
                'user_id' => Auth::id(),
                'type' => $type,
                'quantity' => $diff,
                'old_stock' => $oldStock,
                'new_stock' => $newStock,
                'reference_type' => Product::class,
                'reference_id' => $product->id,
                'note' => $note,
            ]);
        }
        
        return back()->with('success', 'Produk berhasil diupdate.');
    }

    public function destroy(Product $product)
    {
        if ($product->image) Storage::disk('public')->delete($product->image);
        $product->delete();
        return back()->with('success', 'Produk berhasil dihapus.');
    }

    /**
     * Halaman laporan riwayat pergerakan kartu stok (Stock Movements).
     */
    public function stockMovements(Request $request)
    {
        $query = StockMovement::with(['product:id,name,barcode', 'user:id,name']);

        // Filter: Berdasarkan Produk
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->input('product_id'));
        }

        // Filter: Berdasarkan Tipe (in, out, adjustment)
        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        // Filter: Berdasarkan Rentang Tanggal
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                $request->input('start_date') . ' 00:00:00',
                $request->input('end_date') . ' 23:59:59'
            ]);
        }

        return Inertia::render('Products/StockMovements', [
            'movements' => $query->latest()->get(),
            'products'  => Product::select('id', 'name', 'barcode')->get(),
            'filters'   => $request->only(['product_id', 'type', 'start_date', 'end_date'])
        ]);
    }
}
