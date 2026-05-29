<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import DefaultLayout from '@/Layouts/DefaultLayout.vue'

defineOptions({ layout: DefaultLayout })

defineProps({
  stats: Object,
  recent_transactions: Array,
  top_products: Array
})

const page = usePage()
const isAdmin = computed(() => page.props.auth?.user?.role === 'admin')

// Helper to format currency (Rupiah)
const fmtIdr = (v) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0
  }).format(v)
}

// Helper to format number
const fmtNum = (v) => new Intl.NumberFormat('id-ID').format(v)
</script>

<template>
  <div class="dashboard-page container-fluid px-4 py-4">
    <!-- Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-3">
      <div>
        <h3 class="m-0 fw-bold text-primary">Ringkasan Dashboard</h3>
        <p class="text-body-secondary m-0 mt-1">Selamat datang kembali! Berikut adalah ringkasan kinerja toko Anda hari ini.</p>
      </div>
      <!-- Quick Actions -->
      <div class="d-flex align-items-center gap-2">
        <Link href="/pos" class="btn btn-primary btn-sm d-flex align-items-center gap-2 px-3 py-2 fw-semibold rounded-3 shadow-sm">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
          Buka POS Kasir
        </Link>
        <Link v-if="isAdmin" href="/reports/sales" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-2 px-3 py-2 fw-semibold rounded-3 bg-white">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
          Laporan Pendapatan
        </Link>
      </div>
    </div>

    <!-- Stat Cards Grid -->
    <div class="row g-3 mb-4">
      <!-- 1. Revenue Today -->
      <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-4 p-3 card-stat h-100">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <span class="text-secondary small fw-semibold d-block mb-1">Pendapatan Hari Ini</span>
              <h4 class="m-0 fw-bold text-primary font-monospace">{{ fmtIdr(stats.revenue_today) }}</h4>
            </div>
            <div class="bg-primary-subtle text-primary rounded-3 p-2.5 d-flex align-items-center justify-content-center">
              <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
            </div>
          </div>
        </div>
      </div>

      <!-- 2. Transactions Today -->
      <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-4 p-3 card-stat h-100">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <span class="text-secondary small fw-semibold d-block mb-1">Transaksi Hari Ini</span>
              <h4 class="m-0 fw-bold text-dark">{{ fmtNum(stats.sales_today_count) }} Transaksi</h4>
            </div>
            <div class="bg-success-subtle text-success rounded-3 p-2.5 d-flex align-items-center justify-content-center">
              <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
            </div>
          </div>
        </div>
      </div>

      <!-- 3. Total Products -->
      <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-4 p-3 card-stat h-100">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <span class="text-secondary small fw-semibold d-block mb-1">Total Produk Aktif</span>
              <h4 class="m-0 fw-bold text-dark">{{ fmtNum(stats.total_products) }} Item</h4>
            </div>
            <div class="bg-info-subtle text-info rounded-3 p-2.5 d-flex align-items-center justify-content-center">
              <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
            </div>
          </div>
        </div>
      </div>

      <!-- 4. Low Stock WARNING -->
      <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm rounded-4 p-3 card-stat h-100" :class="{ 'warning-glow': stats.low_stock_count > 0 }">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <span class="small fw-semibold d-block mb-1" :class="stats.low_stock_count > 0 ? 'text-danger fw-bold' : 'text-secondary'">Stok Menipis</span>
              <h4 class="m-0 fw-bold" :class="stats.low_stock_count > 0 ? 'text-danger' : 'text-dark'">{{ fmtNum(stats.low_stock_count) }} Produk</h4>
            </div>
            <div class="rounded-3 p-2.5 d-flex align-items-center justify-content-center" :class="stats.low_stock_count > 0 ? 'bg-danger text-white' : 'bg-secondary-subtle text-secondary'">
              <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Details Row -->
    <div class="row g-4">
      <!-- Left side: Recent Transactions -->
      <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 h-100" style="background: white;">
          <div class="card-header bg-white border-0 py-3 px-4 d-flex align-items-center justify-content-between">
            <h5 class="m-0 fw-bold text-dark">Transaksi Terbaru</h5>
            <Link v-if="isAdmin" href="/reports/sales" class="btn btn-link btn-sm text-decoration-none fw-semibold">Lihat Semua</Link>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th class="px-4">No. Invoice</th>
                    <th>Customer</th>
                    <th>Waktu</th>
                    <th>Metode</th>
                    <th class="text-end px-4">Grand Total</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="tx in recent_transactions" :key="tx.id">
                    <td class="px-4"><span class="font-monospace fw-bold text-primary">{{ tx.invoice_no }}</span></td>
                    <td class="fw-medium text-dark">{{ tx.customer }}</td>
                    <td class="text-body-secondary small">{{ tx.time }}</td>
                    <td>
                      <span class="badge text-uppercase font-monospace px-2.5 py-1" :class="tx.payment_method === 'cash' ? 'bg-success-subtle text-success' : 'bg-primary-subtle text-primary'">
                        {{ tx.payment_method }}
                      </span>
                    </td>
                    <td class="text-end px-4 font-monospace fw-bold text-dark">{{ fmtIdr(tx.grand_total) }}</td>
                  </tr>
                  <tr v-if="recent_transactions.length === 0">
                    <td colspan="5" class="text-center py-5 text-body-secondary">Belum ada transaksi hari ini.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- Right side: Top Selling Products -->
      <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 h-100" style="background: white;">
          <div class="card-header bg-white border-0 py-3 px-4">
            <h5 class="m-0 fw-bold text-dark">Produk Terlaris (30 Hari Terakhir)</h5>
          </div>
          <div class="card-body px-4 pb-4">
            <div class="top-selling-list d-flex flex-column gap-3 mt-2">
              <div v-for="(prod, i) in top_products" :key="i" class="top-item d-flex align-items-center gap-3">
                <!-- Rank number badge -->
                <div class="rank-badge rounded-circle d-flex align-items-center justify-content-center text-white fw-bold font-monospace shadow-sm"
                     :class="i === 0 ? 'bg-warning' : (i === 1 ? 'bg-secondary' : (i === 2 ? 'bg-danger' : 'bg-light text-secondary'))"
                     style="width: 28px; height: 28px; font-size: 13px;">
                  {{ i + 1 }}
                </div>
                <!-- Product info -->
                <div class="flex-grow-1">
                  <div class="fw-bold text-dark small">{{ prod.name }}</div>
                  <div class="text-body-secondary font-monospace" style="font-size: 11.5px;">{{ fmtIdr(prod.selling_price) }}</div>
                </div>
                <!-- Total Sold status -->
                <div class="text-end">
                  <span class="badge bg-primary-subtle text-primary px-2.5 py-1.5 rounded-pill font-monospace" style="font-weight: 600;">
                    {{ prod.total_sold }} terjual
                  </span>
                </div>
              </div>
              <div v-if="top_products.length === 0" class="text-center py-5 text-body-secondary">
                Belum ada produk terjual dalam 30 hari terakhir.
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.card-stat {
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  background: white;
}
.card-stat:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05) !important;
}

.warning-glow {
  background: linear-gradient(135deg, #fff5f5 0%, #ffe3e3 100%);
  border: 1px solid rgba(220, 53, 69, 0.1) !important;
  animation: pulse-warning 2s infinite ease-in-out;
}

@keyframes pulse-warning {
  0% { box-shadow: 0 4px 6px rgba(220, 53, 69, 0.05); }
  50% { box-shadow: 0 4px 15px rgba(220, 53, 69, 0.15); }
  100% { box-shadow: 0 4px 6px rgba(220, 53, 69, 0.05); }
}

.table th {
  font-weight: 600;
  font-size: 13px;
  color: var(--cui-secondary-color, #4f5d73);
}

.rank-badge.bg-light {
  border: 1px solid rgba(0,0,0,0.06);
}
</style>
