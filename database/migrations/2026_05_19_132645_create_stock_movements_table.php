<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            
            // Relasi produk (Gunakan restrict agar log histori audit tidak rusak jika produk terhapus)
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            
            // Relasi user/aktor yang memicu pergerakan
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            
            // Jenis Mutasi: in (masuk), out (keluar), atau adjustment (penyesuaian manual/opname)
            $table->enum('type', ['in', 'out', 'adjustment']);
            
            // Jumlah barang bermutasi
            $table->integer('quantity');
            
            // Kondisi sisa stok sebelum & setelah pergerakan untuk audit trail kartu stok
            $table->integer('old_stock');
            $table->integer('new_stock');
            
            // Polymorphic Reference (Menghubungkan ke TransactionDetail, StockAdjustment, dll)
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            
            // Catatan atau alasan pergerakan stok
            $table->text('note')->nullable();
            
            $table->timestamps();

            // Indexing agar laporan kartu stok bulanan/tahunan sangat cepat dimuat
            $table->index(['product_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
