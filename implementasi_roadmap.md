Buatkan aplikasi Point of Sale (POS) / Kasir Toko menggunakan:

TECH STACK

- Backend: Laravel (latest)
- Frontend: Vue.js 3
- Inertia.js
- Database: MySQL
- Build Tool: Vite
- Authentication: Laravel Session Authentication + Middleware
- State Management: Pinia atau Composable jika diperlukan

PENTING:
Saya sudah memiliki template admin UI, jadi fokus pada integrasi komponen dan fitur ke template yang ada.

JANGAN:

- Membuat UI baru dari nol
- Membuat design terlalu fancy
- Membuat tampilan terlalu AI-generated

UI harus:

- Clean
- Modern
- Cepat
- Professional
- Cocok untuk toko, warung, retail kecil-menengah, dan bengkel

Gunakan best practice:

- Clean architecture
- Modular structure
- Reusable components
- Production-ready
- Maintainable code
- Scalable folder structure
- Clean naming convention

===================================
FITUR APLIKASI
===================================

1. AUTHENTICATION & ROLE

Role:

1. Admin

- Full access
- User management
- Master data
- Laporan

2. Kasir

- Transaksi penjualan
- Melihat produk
- Print struk
- Tidak bisa hapus master data

3. Owner

- Dashboard monitoring
- Statistik penjualan
- Profit report

Fitur:

- Login
- Logout
- Remember me
- Middleware role permission
- Route protection

# =================================== 2. DASHBOARD

Dashboard cepat dan ringan:

Summary:

- Penjualan hari ini
- Total transaksi
- Produk terjual
- Stok menipis
- Profit hari ini
- Omset bulan ini

Chart:

- Penjualan harian
- Penjualan bulanan
- Produk terlaris

Activity:

- Transaksi terbaru
- User activity

# =================================== 3. MASTER PRODUK

CRUD Produk

Field:

- SKU / Barcode (OPTIONAL)
- Nama produk
- Kategori
- Supplier
- Harga modal
- Harga jual
- Margin profit otomatis
- Stock
- Minimum stock
- Satuan
- Deskripsi
- Foto produk
- Status aktif/nonaktif

Barcode bersifat OPTIONAL:
Karena tidak semua toko memakai barcode.

Support:

1. Search nama produk
2. Search SKU
3. Barcode scanner (optional)

Fitur:

- Search realtime
- Filter kategori
- Pagination
- Sorting
- Import/Export Excel
- Stock warning

# =================================== 4. KATEGORI

CRUD:

- Nama kategori
- Deskripsi

# =================================== 5. SUPPLIER

CRUD:

- Nama supplier
- Kontak
- WhatsApp
- Email
- Alamat

# =================================== 6. TRANSAKSI POS / KASIR

Buat POS cepat seperti minimarket.

Fitur:

- Search realtime
- Klik produk ke cart
- Barcode scan optional
- Keyboard friendly

Cart:

- Tambah/Kurangi qty
- Remove item
- Diskon item
- Diskon transaksi
- Catatan transaksi

Perhitungan otomatis:

- Subtotal
- Pajak
- Diskon
- Grand total
- Kembalian

Payment:

- Cash
- Transfer
- QRIS
- E-wallet

Fitur tambahan:

- Hold transaction
- Resume transaction
- Draft transaction
- Quick checkout

Setelah checkout:

- Generate invoice
- Print thermal receipt
- Simpan histori transaksi

PENTING:
Saat transaksi berhasil:
Stock HARUS otomatis berkurang.

# =================================== 7. INVENTORY / MANAJEMEN STOK

Gunakan Inventory Movement System.

Fitur:

- Stock masuk
- Stock keluar
- Stock adjustment
- Stock history
- Minimum stock alert
- Mutasi stok

PENTING:
Gunakan sistem stock movement.

Contoh:
Pembelian → stock bertambah
Penjualan → stock berkurang
Retur penjualan → stock bertambah
Retur pembelian → stock berkurang

Simpan histori stock movement untuk audit.

# =================================== 8. PEMBELIAN BARANG

Fitur:

- Input pembelian
- Pilih supplier
- Draft purchase
- Status:
    - Pending
    - Received
    - Cancelled

PENTING:
Saat status "Received":
Stock otomatis bertambah.

# =================================== 9. RETUR

Retur pembelian:

- Barang rusak
- Salah kirim

Retur penjualan:

- Refund customer

Stock harus otomatis menyesuaikan.

# =================================== 10. LAPORAN

Laporan:

- Penjualan harian
- Penjualan bulanan
- Produk terlaris
- Profit report
- Stock report
- Cashflow sederhana
- Kasir report

Filter:

- Date range
- Produk
- Kasir
- Supplier
- Kategori

Export:

- Excel
- PDF
- Print

# =================================== 11. SETTINGS TOKO

Pengaturan:

- Nama toko
- Logo
- Footer struk
- Pajak default
- Mata uang
- Printer thermal
- Kontak toko

===================================
FRONTEND STANDARD LIBRARY
===================================

Gunakan library berikut:

1. Alert & Notification
   Gunakan SweetAlert2 (GLOBAL)

Buat reusable helper:

- showSuccess()
- showError()
- showConfirm()
- showLoading()
- closeLoading()

Support:

- Success
- Error
- Confirmation
- Toast
- Loading
- Session expired

2. Select Dropdown
   Gunakan vue-select

Requirement:

- Searchable
- Async select
- Clearable
- Reusable component

Buat:
BaseSelect.vue

3. Date Picker
   Gunakan:
   @vuepic/vue-datepicker

Support:

- Single date
- Date range
- Month picker

Buat:
BaseDatePicker.vue

4. Data Table
   Gunakan TanStack Table

Requirement:

- Search global
- Search box di setiap header column
- Sorting
- Filtering
- Sticky header
- Pagination
- Responsive
- Loading state
- Empty state
- Bulk delete
- Export

Buat reusable:
BaseDataTable.vue

Support:

- debounce search
- server-side filtering

===================================
FORM STANDARDIZATION
===================================

Gunakan reusable component:

- BaseInput.vue
- BaseSelect.vue
- BaseTextarea.vue
- BaseDatePicker.vue
- BaseModal.vue
- BaseButton.vue
- BaseDataTable.vue

Form harus support:

- validation
- error state
- loading state
- disabled state

===================================
SECURITY
===================================

- CSRF Protection
- Middleware Auth
- Role Permission
- Validation Request
- SQL Injection Prevention
- XSS Protection

===================================
OUTPUT YANG DIINGINKAN
===================================

Generate secara bertahap:

1. Analisa sistem
2. Database design
3. ERD database
4. Struktur folder Laravel + Vue + Inertia
5. Relasi database
6. MVP roadmap
7. Modul prioritas
8. Struktur halaman
9. Struktur reusable component
10. Baru coding per module

11. Icon pakai Featehericons
    Jangan langsung generate semua code sekaligus.

Fokus:
Production-ready, clean code, scalable, cepat, dan mudah di-maintain.
