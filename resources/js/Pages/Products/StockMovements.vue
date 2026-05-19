<script setup>
import { ref, h, watch } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import DefaultLayout from '@/Layouts/DefaultLayout.vue'
import BaseDataTable from '@/Components/Base/BaseDataTable.vue'
import BaseDatePicker from '@/Components/Base/BaseDatePicker.vue'

defineOptions({ layout: DefaultLayout })

const props = defineProps({
  movements: Array,
  products: Array,
  filters: Object
})

// Setup reactive filter states from props (keep them as Date-compatible strings or objects)
const selectedProduct = ref(props.filters.product_id || '')
const selectedType = ref(props.filters.type || '')
const startDate = ref(props.filters.start_date ? new Date(props.filters.start_date) : null)
const endDate = ref(props.filters.end_date ? new Date(props.filters.end_date) : null)

// Watch props.filters to keep state fully in sync on reload or reset
watch(() => props.filters, (newFilters) => {
  selectedProduct.value = newFilters.product_id || ''
  selectedType.value = newFilters.type || ''
  startDate.value = newFilters.start_date ? new Date(newFilters.start_date) : null
  endDate.value = newFilters.end_date ? new Date(newFilters.end_date) : null
}, { deep: true })

// Helper to format currency/numbers nicely
const fmtNum = (v) => new Intl.NumberFormat('id-ID').format(v)

// Helper to format date beautifully for table rows
const fmtDate = (dateStr) => {
  if (!dateStr) return '—'
  const date = new Date(dateStr)
  return date.toLocaleString('id-ID', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  }).replace(/\./g, ':')
}

// Helper to format date to YYYY-MM-DD for backend request parameter
const formatDateToYmd = (date) => {
  if (!date) return ''
  const d = new Date(date)
  if (isNaN(d.getTime())) return '' // Invalid date fallback
  
  const month = '' + (d.getMonth() + 1)
  const day = '' + d.getDate()
  const year = d.getFullYear()

  return [year, month.padStart(2, '0'), day.padStart(2, '0')].join('-')
}

// Apply Filters function (server-side pre-filters)
const applyFilters = () => {
  router.get('/products/stock-movements', {
    product_id: selectedProduct.value,
    type: selectedType.value,
    start_date: formatDateToYmd(startDate.value),
    end_date: formatDateToYmd(endDate.value)
  }, {
    preserveState: true,
    preserveScroll: true
  })
}

// Reset Filters function
const resetFilters = () => {
  selectedProduct.value = ''
  selectedType.value = null
  startDate.value = null
  endDate.value = null
  router.get('/products/stock-movements', {}, {
    preserveState: true,
    preserveScroll: true
  })
}

// TanStack Table Column Definitions
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
    accessorKey: 'created_at',
    header: 'Waktu',
    size: 160,
    enableSorting: true,
    enableColumnFilter: false,
    cell: ({ getValue }) => h('span', { class: 'font-monospace text-dark', style: 'font-size: 13px;' }, fmtDate(getValue()))
  },
  {
    accessorKey: 'product_name',
    header: 'Produk',
    enableSorting: true,
    enableColumnFilter: true,
    accessorFn: (row) => row.product?.name ?? 'Produk Dihapus',
    cell: ({ row: { original: mv } }) => {
      const prod = mv.product
      return h('div', [
        h('div', { class: 'fw-bold text-dark', style: 'font-size: 14.5px;' }, prod ? prod.name : 'Produk Dihapus'),
        h('div', { class: 'text-body-secondary font-monospace mt-0.5', style: 'font-size: 11.5px;' }, `Barcode: ${prod?.barcode ?? '—'}`)
      ])
    }
  },
  {
    accessorKey: 'type',
    header: 'Tipe Mutasi',
    size: 160,
    enableSorting: true,
    enableColumnFilter: true,
    cell: ({ getValue }) => {
      const type = getValue()
      if (type === 'in') {
        return h('span', { class: 'badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1.5 rounded-pill d-inline-flex align-items-center gap-1' }, [
          h('svg', { xmlns: 'http://www.w3.org/2000/svg', width: '12', height: '12', viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '2.5', 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }, [
            h('line', { x1: '12', y1: '5', x2: '12', y2: '19' }),
            h('polyline', { points: '19 12 12 19 5 12' })
          ]),
          ' Barang Masuk'
        ])
      } else if (type === 'out') {
        return h('span', { class: 'badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1.5 rounded-pill d-inline-flex align-items-center gap-1' }, [
          h('svg', { xmlns: 'http://www.w3.org/2000/svg', width: '12', height: '12', viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '2.5', 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }, [
            h('line', { x1: '12', y1: '19', x2: '12', y2: '5' }),
            h('polyline', { points: '5 12 12 5 19 12' })
          ]),
          ' Barang Keluar'
        ])
      } else {
        return h('span', { class: 'badge bg-warning-subtle text-warning border border-warning-subtle px-2.5 py-1.5 rounded-pill d-inline-flex align-items-center gap-1' }, [
          h('svg', { xmlns: 'http://www.w3.org/2000/svg', width: '12', height: '12', viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '2.5', 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }, [
            h('path', { d: 'M20 11.08V12a8 8 0 1 1-4.8-7.32' }),
            h('polyline', { points: '22 4 12 14.01 9 11.01' })
          ]),
          ' Koreksi Stok'
        ])
      }
    }
  },
  {
    accessorKey: 'quantity',
    header: 'Jumlah',
    size: 110,
    enableSorting: true,
    enableColumnFilter: false,
    cell: ({ row: { original: mv } }) => {
      const type = mv.type
      const qtyClass = type === 'in' ? 'text-success' : (type === 'out' ? 'text-danger' : 'text-warning')
      const prefix = type === 'in' ? '+' : (type === 'out' ? '-' : '±')
      return h('span', { class: `font-monospace fw-bold ${qtyClass}` }, `${prefix}${fmtNum(mv.quantity)}`)
    }
  },
  {
    accessorKey: 'old_stock',
    header: 'Stok Awal',
    size: 120,
    enableSorting: true,
    enableColumnFilter: false,
    cell: ({ getValue }) => h('span', { class: 'font-monospace text-secondary' }, fmtNum(getValue()))
  },
  {
    accessorKey: 'new_stock',
    header: 'Stok Akhir',
    size: 120,
    enableSorting: true,
    enableColumnFilter: false,
    cell: ({ getValue }) => h('span', { class: 'font-monospace text-dark fw-bold' }, fmtNum(getValue()))
  },
  {
    accessorKey: 'user_name',
    header: 'Aktor (PIC)',
    size: 150,
    enableSorting: true,
    enableColumnFilter: true,
    accessorFn: (row) => row.user?.name ?? 'System',
    cell: ({ row: { original: mv } }) => {
      const uName = mv.user?.name ?? 'System'
      const firstChar = uName.charAt(0).toUpperCase()
      return h('span', { class: 'd-flex align-items-center text-dark fw-medium' }, [
        h('div', { class: 'bg-primary text-white rounded-circle d-flex align-items-center justify-content-center text-uppercase font-monospace fw-bold me-2', style: 'width: 22px; height: 22px; font-size: 11px;' }, firstChar),
        uName
      ])
    }
  },
  {
    accessorKey: 'note',
    header: 'Catatan Alasan',
    enableSorting: true,
    enableColumnFilter: true,
    cell: ({ getValue }) => h('span', { class: 'text-body-secondary', style: 'font-size: 13.5px;' }, getValue() ?? '—')
  }
]
</script>

<template>
  <div class="pos-page container-fluid px-4 py-4">
    <!-- Header Premium -->
    <div class="d-flex align-items-center justify-content-between mb-4">
      <div>
        <h3 class="m-0 fw-bold text-primary d-flex align-items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="feather text-primary"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
          Riwayat Kartu Stok (Stock Movements)
        </h3>
        <p class="text-body-secondary m-0 mt-1">Pantau rekaman pergerakan masuk, keluar, dan penyesuaian stok produk secara mendetail.</p>
      </div>
      <!-- Tombol Kembali Ke Produk -->
      <Link href="/products" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-2 px-3 py-2 fw-semibold">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
        Kembali ke Produk
      </Link>
    </div>

    <!-- Filter Card Premium (Glassmorphism & Sleek) -->
    <div class="card border-0 shadow-sm mb-4 rounded-4" style="background: rgba(255, 255, 255, 0.95); border: 1px solid rgba(0, 0, 0, 0.03) !important;">
      <div class="card-body p-4 pb-1">
        <h6 class="fw-bold mb-3 d-flex align-items-center gap-2 text-dark">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
          Filter Kartu Stok
        </h6>
        
        <div class="row g-3 align-items-end">
          <!-- Filter Produk -->
          <div class="col-md-3 mb-3">
            <label class="form-label fw-semibold text-secondary small mb-2">Pilih Produk</label>
            <select v-model="selectedProduct" class="form-select form-select-sm rounded-3" style="height: 38px;">
              <option value="">Semua Produk</option>
              <option v-for="prod in products" :key="prod.id" :value="prod.id">
                {{ prod.name }} (Barcode: {{ prod.barcode ?? '—' }})
              </option>
            </select>
          </div>

          <!-- Filter Tipe Mutasi -->
          <div class="col-md-3 mb-3">
            <label class="form-label fw-semibold text-secondary small mb-2">Tipe Pergerakan</label>
            <select v-model="selectedType" class="form-select form-select-sm rounded-3" style="height: 38px;">
              <option value="">Semua Tipe</option>
              <option value="in">Barang Masuk (Inbound)</option>
              <option value="out">Barang Keluar (Outbound)</option>
              <option value="adjustment">Koreksi Stok (Adjustment)</option>
            </select>
          </div>

          <!-- Filter Tanggal Mulai -->
          <div class="col-md-2">
            <BaseDatePicker
              v-model="startDate"
              label="Dari Tanggal"
              placeholder="Pilih tanggal..."
            />
          </div>

          <!-- Filter Tanggal Selesai -->
          <div class="col-md-2">
            <BaseDatePicker
              v-model="endDate"
              label="Sampai Tanggal"
              placeholder="Pilih tanggal..."
            />
          </div>

          <!-- Tombol Aksi Filter -->
          <div class="col-md-2 d-flex gap-2 mb-3" style="height: 38px;">
            <button @click="applyFilters" class="btn btn-primary btn-sm flex-grow-1 rounded-3 fw-bold d-flex align-items-center justify-content-center gap-2" style="height: 38px; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
              Cari
            </button>
            <button @click="resetFilters" class="btn btn-light btn-sm rounded-3 fw-bold border" style="height: 38px; width: 42px;" title="Reset Filter">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 4v6h-6"></path><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path></svg>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Riwayat Tabel Card -->
    <div class="card border-0 shadow-sm rounded-4" style="overflow: hidden;">
      <div class="card-body p-3">
        <BaseDataTable
          :columns="columns"
          :data="movements"
          :selectable="false"
          search-placeholder="Cari produk, aktor, catatan..."
          empty-text="Tidak ada riwayat pergerakan stok ditemukan."
        />
      </div>
    </div>
  </div>
</template>

<style scoped>
:deep(.badge) {
  font-weight: 600;
  letter-spacing: 0.2px;
}
:deep(.bg-success-subtle) {
  background-color: rgba(25, 135, 84, 0.08) !important;
}
:deep(.border-success-subtle) {
  border-color: rgba(25, 135, 84, 0.2) !important;
}
:deep(.bg-danger-subtle) {
  background-color: rgba(220, 53, 69, 0.08) !important;
}
:deep(.border-danger-subtle) {
  border-color: rgba(220, 53, 69, 0.2) !important;
}
:deep(.bg-warning-subtle) {
  background-color: rgba(255, 193, 7, 0.08) !important;
}
:deep(.border-warning-subtle) {
  border-color: rgba(255, 193, 7, 0.3) !important;
}
</style>
