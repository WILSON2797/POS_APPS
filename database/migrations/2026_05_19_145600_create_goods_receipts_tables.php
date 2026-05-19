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
        // Table: goods_receipts (Header)
        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_no', 100)->unique();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // Admin who recorded the receipt
            $table->date('receipt_date');
            $table->string('reference_no', 150)->nullable(); // Supplier Invoice / Delivery Order (Surat Jalan) No
            $table->decimal('total_cost', 15, 2)->default(0.00);
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Table: goods_receipt_details (Items)
        Schema::create('goods_receipt_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_receipt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->integer('qty');
            $table->decimal('cost_price', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_details');
        Schema::dropIfExists('goods_receipts');
    }
};
