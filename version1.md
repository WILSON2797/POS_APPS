# 🏪 POS App - Implementation Roadmap

## Tech Stack

- **Backend**: Laravel 10 + PHP 8.2
- **Frontend**: Vue 3 (Composition API) + CoreUI Template
- **Bridge**: Inertia.js
- **Database**: MySQL
- **Auth**: Laravel Breeze + Custom Roles
- **Build**: Vite

---

## Phase 1: Foundation Setup ⏳ // Done 17 may 2026

- [ ] Create Laravel project
- [ ] Install & configure Inertia.js
- [ ] Integrate CoreUI template into Laravel
- [ ] Setup authentication (Login, Logout, Remember me)
- [ ] Create role system (Admin, Kasir, Owner)
- [ ] Database migrations (users, roles)
- [ ] Seeders (default admin user)
- [ ] Middleware role protection

## Phase 2: Master Data

- [ ] Kategori Produk (CRUD)
- [ ] Supplier (CRUD)
- [ ] Customer/Member (CRUD)
- [ ] Produk (CRUD + barcode, stock, image)
- [ ] Reusable DataTable component
- [ ] Search, filter, pagination, sort

## Phase 3: Transaksi POS (Kasir)

- [ ] Halaman kasir modern
- [ ] Search produk realtime
- [ ] Cart system (tambah, kurang, hapus)
- [ ] Diskon item & transaksi
- [ ] Perhitungan otomatis (subtotal, pajak, total, kembalian)
- [ ] Payment methods (Cash, Transfer, QRIS, E-wallet)
- [ ] Hold/Resume transaction
- [ ] Print thermal receipt
- [ ] Invoice generation

## Phase 4: Inventory & Pembelian

- [ ] Stock masuk/keluar/adjustment
- [ ] Stock movement history
- [ ] Alert stock minimum
- [ ] Purchase module (input, supplier, status)
- [ ] Auto update stock on purchase

## Phase 5: Dashboard & Laporan

- [ ] Dashboard summary cards
- [ ] Charts (penjualan harian, bulanan, produk terlaris)
- [ ] Laporan penjualan, profit, stock, kasir
- [ ] Filter date range
- [ ] Export Excel/PDF/Print

## Phase 6: Settings & Polish

- [ ] Settings toko (nama, logo, footer struk, pajak)
- [ ] Retur pembelian & penjualan
- [ ] User management
- [ ] Keyboard shortcuts
- [ ] Performance optimization

---

## Database ERD (Core Tables)

```
users ──────────── roles
  │
  ├── transactions ──── transaction_items ──── products
  │                                              │
  │                                          categories
  │                                          suppliers
  │
  ├── purchases ──── purchase_items ──── products
  │
  └── stock_movements ──── products

customers ──── transactions
settings (key-value store)
```

## Priority: MVP First

**Phase 1 → Phase 2 → Phase 3** = Minimum viable POS system
