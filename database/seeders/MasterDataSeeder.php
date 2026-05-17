<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Supplier;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        // Categories
        $categories = [
            ['name' => 'Minuman', 'description' => 'Berbagai jenis minuman'],
            ['name' => 'Makanan', 'description' => 'Makanan ringan dan berat'],
            ['name' => 'Snack', 'description' => 'Camilan dan snack'],
            ['name' => 'Sembako', 'description' => 'Kebutuhan pokok sehari-hari'],
            ['name' => 'Personal Care', 'description' => 'Produk perawatan diri'],
            ['name' => 'Elektronik', 'description' => 'Aksesoris elektronik'],
        ];
        foreach ($categories as $cat) Category::create($cat);

        // Suppliers
        $suppliers = [
            ['name' => 'PT Indofood', 'contact_person' => 'Budi Santoso', 'phone' => '021-5555001', 'whatsapp' => '08111000001', 'email' => 'budi@indofood.com', 'address' => 'Jakarta Selatan'],
            ['name' => 'PT Unilever', 'contact_person' => 'Siti Rahayu', 'phone' => '021-5555002', 'whatsapp' => '08111000002', 'email' => 'siti@unilever.com', 'address' => 'Tangerang'],
            ['name' => 'CV Maju Jaya', 'contact_person' => 'Ahmad', 'phone' => '031-5555003', 'whatsapp' => '08111000003', 'email' => 'ahmad@majujaya.com', 'address' => 'Surabaya'],
        ];
        foreach ($suppliers as $sup) Supplier::create($sup);

        // Customers
        $customers = [
            ['name' => 'Andi Pratama', 'phone' => '081234567890', 'email' => 'andi@email.com', 'address' => 'Jl. Merdeka No.1', 'points' => 150, 'level' => 'silver'],
            ['name' => 'Budi Hartono', 'phone' => '082345678901', 'email' => 'budi@email.com', 'address' => 'Jl. Sudirman No.10', 'points' => 500, 'level' => 'gold'],
            ['name' => 'Citra Dewi', 'phone' => '083456789012', 'email' => null, 'address' => null, 'points' => 20, 'level' => 'regular'],
        ];
        foreach ($customers as $cust) Customer::create($cust);

        // Products
        $minuman = Category::where('name', 'Minuman')->first();
        $makanan = Category::where('name', 'Makanan')->first();
        $snack = Category::where('name', 'Snack')->first();
        $sembako = Category::where('name', 'Sembako')->first();
        $supplier1 = Supplier::first();

        $products = [
            ['barcode' => '8991234000001', 'name' => 'Aqua 600ml', 'category_id' => $minuman->id, 'supplier_id' => $supplier1->id, 'cost_price' => 2500, 'selling_price' => 3500, 'stock' => 100, 'min_stock' => 20, 'unit' => 'botol', 'is_active' => true],
            ['barcode' => '8991234000002', 'name' => 'Indomie Goreng', 'category_id' => $makanan->id, 'supplier_id' => $supplier1->id, 'cost_price' => 2800, 'selling_price' => 3500, 'stock' => 200, 'min_stock' => 30, 'unit' => 'pcs', 'is_active' => true],
            ['barcode' => '8991234000003', 'name' => 'Chitato Ayam', 'category_id' => $snack->id, 'supplier_id' => $supplier1->id, 'cost_price' => 8000, 'selling_price' => 10000, 'stock' => 50, 'min_stock' => 10, 'unit' => 'pcs', 'is_active' => true],
            ['barcode' => '8991234000004', 'name' => 'Beras 5kg', 'category_id' => $sembako->id, 'supplier_id' => null, 'cost_price' => 65000, 'selling_price' => 75000, 'stock' => 5, 'min_stock' => 10, 'unit' => 'karung', 'is_active' => true],
            ['barcode' => '8991234000005', 'name' => 'Teh Botol 350ml', 'category_id' => $minuman->id, 'supplier_id' => $supplier1->id, 'cost_price' => 4000, 'selling_price' => 5000, 'stock' => 80, 'min_stock' => 20, 'unit' => 'botol', 'is_active' => true],
        ];
        foreach ($products as $prod) Product::create($prod);
    }
}
