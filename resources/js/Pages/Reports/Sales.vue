<script setup>
import { ref, h, watch, computed } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import DefaultLayout from '@/Layouts/DefaultLayout.vue'
import BaseDataTable from '@/Components/Base/BaseDataTable.vue'
import BaseDatePicker from '@/Components/Base/BaseDatePicker.vue'

defineOptions({ layout: DefaultLayout })

const props = defineProps({
  transactions: Array,
  summary: Object,
  filters: Object
})

// Setup reactive filter states from props
const selectedPayment = ref(props.filters.payment_method || '')
const startDate = ref(props.filters.start_date ? new Date(props.filters.start_date) : null)
const endDate = ref(props.filters.end_date ? new Date(props.filters.end_date) : null)

// Watch props.filters to keep state fully in sync on reload or reset
watch(() => props.filters, (newFilters) => {
  selectedPayment.value = newFilters.payment_method || ''
  startDate.value = newFilters.start_date ? new Date(newFilters.start_date) : null
  endDate.value = newFilters.end_date ? new Date(newFilters.end_date) : null
}, { deep: true })

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

// Helper to format date to YYYY-MM-DD for backend parameter
const formatDateToYmd = (date) => {
  if (!date) return ''
  const d = new Date(date)
  if (isNaN(d.getTime())) return ''
  
  const month = '' + (d.getMonth() + 1)
  const day = '' + d.getDate()
  const year = d.getFullYear()

  return [year, month.padStart(2, '0'), day.padStart(2, '0')].join('-')
}

// Apply filters
const applyFilters = () => {
  const query = {}
  
  if (selectedPayment.value) {
    query.payment_method = selectedPayment.value
  }
  
  const start = formatDateToYmd(startDate.value)
  if (start) {
    query.start_date = start
  }
  
  const end = formatDateToYmd(endDate.value)
  if (end) {
    query.end_date = end
  }

  router.get('/reports/sales', query, {
    preserveState: true,
    preserveScroll: true
  })
}

// Reset filters
const resetFilters = () => {
  selectedPayment.value = ''
  startDate.value = null
  endDate.value = null
  router.get('/reports/sales', {}, {
    preserveState: true,
    preserveScroll: true
  })
}

// Compute dynamic export URL with active filters
const exportUrl = computed(() => {
  const params = new URLSearchParams()
  if (selectedPayment.value) params.append('payment_method', selectedPayment.value)
  if (startDate.value) params.append('start_date', formatDateToYmd(startDate.value))
  if (endDate.value) params.append('end_date', formatDateToYmd(endDate.value))
  return `/reports/sales/export?${params.toString()}`
})

// Calculate payment method statistics percentages
const paymentStatsProgress = computed(() => {
  const stats = props.summary.payment_stats || {}
  const total = Object.values(stats).reduce((acc, curr) => acc + curr, 0)
  
  const labels = {
    cash: { text: 'Tunai (Cash)', color: 'bg-primary' },
    qris: { text: 'QRIS', color: 'bg-purple' },
    transfer: { text: 'Bank Transfer', color: 'bg-info' },
    'e-wallet': { text: 'E-Wallet', color: 'bg-warning' }
  }

  return Object.keys(labels).map(key => {
    const value = stats[key] || 0
    const percentage = total > 0 ? (value / total) * 100 : 0
    return {
      key,
      name: labels[key].text,
      amount: value,
      percentage: percentage,
      colorClass: labels[key].colorClass || labels[key].color
    }
  }).sort((a, b) => b.amount - a.amount)
})

// Column definitions for TanStack Table
const columns = [
  {
    id: 'no',
    header: '#',
    size: 50,
    enableSorting: false,
    enableColumnFilter: false,
    cell: ({ row }) => row.index + 1
  },
  {
    accessorKey: 'invoice_no',
    header: 'No. Invoice',
    size: 160,
    enableSorting: true,
    enableColumnFilter: true,
    cell: ({ getValue }) => h('span', { class: 'font-monospace fw-bold text-primary' }, getValue())
  },
  {
    accessorKey: 'created_at',
    header: 'Tanggal & Waktu',
    size: 150,
    enableSorting: true,
    enableColumnFilter: false,
    cell: ({ getValue }) => h('span', { class: 'font-monospace text-dark', style: 'font-size: 13px;' }, getValue())
  },
  {
    accessorKey: 'customer',
    header: 'Customer',
    size: 140,
    enableSorting: true,
    enableColumnFilter: true,
    cell: ({ getValue }) => h('span', { class: 'fw-medium text-dark' }, getValue())
  },
  {
    accessorKey: 'cashier',
    header: 'Kasir',
    size: 130,
    enableSorting: true,
    enableColumnFilter: true,
    cell: ({ getValue }) => h('span', { class: 'text-body-secondary' }, getValue())
  },
  {
    accessorKey: 'subtotal',
    header: 'Subtotal',
    size: 120,
    enableSorting: true,
    enableColumnFilter: false,
    cell: ({ getValue }) => h('span', { class: 'font-monospace' }, fmtIdr(getValue()))
  },
  {
    accessorKey: 'discount',
    header: 'Diskon',
    size: 110,
    enableSorting: true,
    enableColumnFilter: false,
    cell: ({ getValue }) => {
      const val = getValue()
      return val > 0
        ? h('span', { class: 'font-monospace text-danger' }, `-${fmtIdr(val)}`)
        : h('span', { class: 'font-monospace text-secondary' }, 'Rp0')
    }
  },
  {
    accessorKey: 'tax',
    header: 'Pajak (PPN)',
    size: 110,
    enableSorting: true,
    enableColumnFilter: false,
    cell: ({ getValue }) => {
      const val = getValue()
      return val > 0
        ? h('span', { class: 'font-monospace text-secondary' }, fmtIdr(val))
        : h('span', { class: 'font-monospace text-secondary' }, 'Rp0')
    }
  },
  {
    accessorKey: 'grand_total',
    header: 'Grand Total',
    size: 130,
    enableSorting: true,
    enableColumnFilter: false,
    cell: ({ getValue }) => h('span', { class: 'font-monospace fw-bold text-dark' }, fmtIdr(getValue()))
  },
  {
    accessorKey: 'payment_method',
    header: 'Metode',
    size: 120,
    enableSorting: true,
    enableColumnFilter: true,
    cell: ({ getValue }) => {
      const method = getValue()
      let bgClass = 'bg-primary-subtle text-primary border-primary-subtle'
      let label = method.toUpperCase()
      
      if (method === 'cash') {
        bgClass = 'bg-success-subtle text-success border-success-subtle'
        label = 'TUNAI'
      } else if (method === 'qris') {
        bgClass = 'bg-purple-subtle text-purple border-purple-subtle'
        label = 'QRIS'
      } else if (method === 'transfer') {
        bgClass = 'bg-info-subtle text-info border-info-subtle'
        label = 'TRANSFER'
      } else if (method === 'e-wallet') {
        bgClass = 'bg-warning-subtle text-warning border-warning-subtle'
        label = 'E-WALLET'
      }

      return h('span', { class: `badge ${bgClass} border px-2 py-1 rounded-pill` }, label)
    }
  },
  {
    accessorKey: 'estimated_profit',
    header: 'Profit Bersih',
    size: 140,
    enableSorting: true,
    enableColumnFilter: false,
    cell: ({ getValue }) => {
      const val = getValue()
      const colorClass = val >= 0 ? 'text-success fw-bold' : 'text-danger fw-bold'
      return h('span', { class: `font-monospace ${colorClass}` }, fmtIdr(val))
    }
  }
]
</script>

<template>
  <div class="reports-sales-page container-fluid px-4 py-4">
    <!-- Header Premium -->
    <div class="d-flex align-items-center justify-content-between mb-4">
      <div>
        <h3 class="m-0 fw-bold text-primary d-flex align-items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-primary">
            <line x1="12" y1="1" x2="12" y2="23"></line>
            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
          </svg>
          Laporan Pendapatan & Penjualan
        </h3>
        <p class="text-body-secondary m-0 mt-1">Pantau performa keuangan kasir, omzet kotor, hingga estimasi margin profit toko Anda.</p>
      </div>
      <div class="d-flex align-items-center gap-2">
        <a :href="exportUrl" class="btn btn-success text-white btn-sm d-flex align-items-center gap-2 px-3 py-2 fw-semibold">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
          Ekspor Excel
        </a>
        <Link href="/dashboard" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-2 px-3 py-2 fw-semibold">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
          Dashboard
        </Link>
      </div>
    </div>

    <!-- Summary Widgets Row -->
    <div class="row g-3 mb-4">
      <!-- 1. Total Sales -->
      <div class="col-md-2.4 col-sm-6">
        <div class="card border-0 shadow-sm rounded-4 h-100 p-3 card-stat">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <span class="text-secondary small fw-semibold d-block mb-1">Transaksi</span>
              <h4 class="m-0 fw-bold text-dark">{{ fmtNum(summary.total_sales) }}</h4>
            </div>
            <div class="bg-primary-subtle text-primary rounded-3 p-2.5 d-flex align-items-center justify-content-center">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
            </div>
          </div>
        </div>
      </div>

      <!-- 2. Total Revenue (Grand Total) -->
      <div class="col-md-2.4 col-sm-6">
        <div class="card border-0 shadow-sm rounded-4 h-100 p-3 card-stat">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <span class="text-secondary small fw-semibold d-block mb-1">Omzet (Grand Total)</span>
              <h4 class="m-0 fw-bold text-primary font-monospace">{{ fmtIdr(summary.total_revenue) }}</h4>
            </div>
            <div class="bg-success-subtle text-success rounded-3 p-2.5 d-flex align-items-center justify-content-center">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
            </div>
          </div>
        </div>
      </div>

      <!-- 3. Total Profit (HIGHLIGHTED GREEN) -->
      <div class="col-md-2.4 col-sm-6">
        <div class="card border-0 shadow-sm rounded-4 h-100 p-3 card-stat highlight-profit">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <span class="text-success-emphasis small fw-bold d-block mb-1">Estimasi Profit Bersih</span>
              <h4 class="m-0 fw-black text-success font-monospace">{{ fmtIdr(summary.total_profit) }}</h4>
            </div>
            <div class="bg-success text-white rounded-3 p-2.5 d-flex align-items-center justify-content-center shadow-sm">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg>
            </div>
          </div>
        </div>
      </div>

      <!-- 4. Total Discount -->
      <div class="col-md-2.4 col-sm-6">
        <div class="card border-0 shadow-sm rounded-4 h-100 p-3 card-stat">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <span class="text-secondary small fw-semibold d-block mb-1">Total Diskon</span>
              <h4 class="m-0 fw-bold text-danger font-monospace">{{ fmtIdr(summary.total_discount) }}</h4>
            </div>
            <div class="bg-danger-subtle text-danger rounded-3 p-2.5 d-flex align-items-center justify-content-center">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>
            </div>
          </div>
        </div>
      </div>

      <!-- 5. Total Tax -->
      <div class="col-md-2.4 col-sm-6">
        <div class="card border-0 shadow-sm rounded-4 h-100 p-3 card-stat">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <span class="text-secondary small fw-semibold d-block mb-1">Total Pajak (PPN)</span>
              <h4 class="m-0 fw-bold text-secondary font-monospace">{{ fmtIdr(summary.total_tax) }}</h4>
            </div>
            <div class="bg-secondary-subtle text-secondary rounded-3 p-2.5 d-flex align-items-center justify-content-center">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-4 mb-4">
      <!-- Left side: Filters -->
      <div class="col-lg-8">
        <!-- Filter Card Premium -->
        <div class="card border-0 shadow-sm rounded-4 h-100" style="background: rgba(255, 255, 255, 0.95); border: 1px solid rgba(0, 0, 0, 0.03) !important;">
          <div class="card-body p-4">
            <h6 class="fw-bold mb-3 d-flex align-items-center gap-2 text-dark">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
              Filter Laporan Keuangan
            </h6>
            
            <div class="row g-3 align-items-end">
              <!-- Filter Tanggal Mulai -->
              <div class="col-md-4">
                <BaseDatePicker
                  v-model="startDate"
                  label="Dari Tanggal"
                  placeholder="Pilih tanggal mulai..."
                />
              </div>

              <!-- Filter Tanggal Selesai -->
              <div class="col-md-4">
                <BaseDatePicker
                  v-model="endDate"
                  label="Sampai Tanggal"
                  placeholder="Pilih tanggal akhir..."
                />
              </div>

              <!-- Filter Metode Pembayaran -->
              <div class="col-md-4">
                <label class="form-label fw-semibold text-secondary small mb-2">Metode Pembayaran</label>
                <select v-model="selectedPayment" class="form-select form-select-sm rounded-3" style="height: 38px;">
                  <option value="">Semua Metode</option>
                  <option value="cash">Tunai (Cash)</option>
                  <option value="qris">QRIS</option>
                  <option value="transfer">Bank Transfer</option>
                  <option value="e-wallet">E-Wallet</option>
                </select>
              </div>

              <!-- Buttons -->
              <div class="col-12 d-flex justify-content-end gap-2 mt-4">
                <button @click="resetFilters" class="btn btn-light rounded-3 fw-bold border px-3" style="height: 38px;" title="Reset Filter">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1"><path d="M23 4v6h-6"></path><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path></svg>
                  Reset
                </button>
                <button @click="applyFilters" class="btn btn-primary rounded-3 fw-bold d-flex align-items-center justify-content-center gap-2 px-4" style="height: 38px; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                  Tampilkan Laporan
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right side: Payment Method Distribution Analytics Card -->
      <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
          <div class="card-body p-4">
            <h6 class="fw-bold mb-3 d-flex align-items-center gap-2 text-dark">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
              Metode Pembayaran Terpopuler
            </h6>
            
            <div class="payment-methods-breakdown d-flex flex-column gap-3 mt-2">
              <div v-for="stat in paymentStatsProgress" :key="stat.key" class="payment-stat-item">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <span class="fw-semibold text-dark small">{{ stat.name }}</span>
                  <span class="font-monospace text-secondary small">{{ fmtIdr(stat.amount) }} ({{ stat.percentage.toFixed(1) }}%)</span>
                </div>
                <!-- Premium Sleek Progress Bar -->
                <div class="progress rounded-pill bg-light" style="height: 8px;">
                  <div 
                    class="progress-bar rounded-pill transition-all" 
                    :class="stat.colorClass" 
                    role="progressbar" 
                    :style="{ width: `${stat.percentage}%` }" 
                    :aria-valuenow="stat.percentage" 
                    aria-valuemin="0" 
                    aria-valuemax="100"
                  ></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Riwayat Transaksi Tabel Card -->
    <div class="card border-0 shadow-sm rounded-4" style="overflow: hidden;">
      <div class="card-header bg-white border-0 py-3 px-4 d-flex align-items-center justify-content-between">
        <h5 class="m-0 fw-bold text-dark">Rincian Transaksi Penjualan</h5>
      </div>
      <div class="card-body p-3">
        <BaseDataTable
          :columns="columns"
          :data="transactions"
          :selectable="false"
          search-placeholder="Cari invoice, kasir, customer..."
          empty-text="Tidak ada rekaman transaksi penjualan dalam periode ini."
        />
      </div>
    </div>
  </div>
</template>

<style scoped>
.col-md-2\.4 {
  flex: 0 0 auto;
  width: 20%;
}
@media (max-width: 991.98px) {
  .col-md-2\.4 {
    width: 33.333333%;
  }
}
@media (max-width: 767.98px) {
  .col-md-2\.4 {
    width: 50%;
  }
}
@media (max-width: 575.98px) {
  .col-md-2\.4 {
    width: 100%;
  }
}

.card-stat {
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  background: white;
}
.card-stat:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05) !important;
}

.highlight-profit {
  background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
  border: 1px solid rgba(22, 163, 74, 0.1) !important;
}
.highlight-profit:hover {
  box-shadow: 0 8px 24px rgba(22, 163, 74, 0.12) !important;
}

/* Custom Colors for progress bars */
.bg-purple {
  background-color: #8b5cf6 !important;
}
.bg-purple-subtle {
  background-color: rgba(139, 92, 246, 0.08) !important;
}
.border-purple-subtle {
  border-color: rgba(139, 92, 246, 0.2) !important;
}
.text-purple {
  color: #8b5cf6 !important;
}

.transition-all {
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

:deep(.badge) {
  font-weight: 600;
  letter-spacing: 0.2px;
}
</style>
