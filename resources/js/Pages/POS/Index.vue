<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import DefaultLayout from '@/Layouts/DefaultLayout.vue'
import BaseButton from '@/Components/Base/BaseButton.vue'
import BaseSelect from '@/Components/Base/BaseSelect.vue'
import BaseInput from '@/Components/Base/BaseInput.vue'
import BaseModal from '@/Components/Base/BaseModal.vue'
import { useCartStore } from '@/stores/cart'
import { useSwal } from '@/composables/useSwal'

import { usePage } from '@inertiajs/vue3'

defineOptions({ layout: DefaultLayout })

const props = defineProps({
  products:   Array,
  categories: Array,
  customers:  Array,
})

const page = usePage()
const cartStore = useCartStore()
const { showSuccess, showError, showConfirm } = useSwal()

// Filter and search products state
const searchQuery = ref('')
const selectedCategoryId = ref('')

const filteredProducts = computed(() => {
  return props.products.filter(p => {
    const matchesSearch = searchQuery.value
      ? p.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        (p.barcode && p.barcode.toLowerCase().includes(searchQuery.value.toLowerCase()))
      : true
    const matchesCategory = selectedCategoryId.value
      ? p.category_id === selectedCategoryId.value
      : true
    return matchesSearch && matchesCategory
  })
})

// Quick cash buttons
const quickCashAmounts = [10000, 20000, 50000, 100000, 150000, 200000]

// Selected customer selection computed
const selectedCustomer = computed({
  get: () => cartStore.customer?.id || null,
  set: (val) => {
    const cust = props.customers.find(c => c.id === val)
    cartStore.setCustomer(cust || null)
  }
})

// Item manipulation
function addToCart(p) {
  const cartItem = cartStore.items.find(i => i.product.id === p.id)
  const currentQty = cartItem ? cartItem.qty : 0
  if (p.stock <= currentQty) {
    showError(`Stok produk "${p.name}" tidak mencukupi (Tersedia ${p.stock}).`)
    return
  }
  cartStore.addItem(p)
}

function handleQtyChange(productId, qty) {
  cartStore.updateQty(productId, qty)
}

// Receipt & Transaction Processing
const showReceiptModal = ref(false)
const receiptData = ref(null)
const checkoutForm = useForm({
  items: [],
  customer_id: null,
  discount_type: 'flat',
  discount_value: 0,
  tax_rate: 11,
  payment_method: 'cash',
  amount_paid: 0,
  note: '',
})

function setQuickCash(amount) {
  if (amount === 'pas') {
    cartStore.setAmountPaid(cartStore.grandTotal)
  } else {
    cartStore.setAmountPaid(amount)
  }
}

async function processCheckout() {
  if (cartStore.items.length === 0) {
    showError('Keranjang belanja kosong.')
    return
  }

  if (cartStore.paymentMethod === 'cash' && cartStore.amountPaid < cartStore.grandTotal) {
    showError('Jumlah pembayaran kurang dari total belanja.')
    return
  }

  const result = await showConfirm({
    title: 'Konfirmasi Transaksi',
    text: `Proses transaksi sebesar Rp ${fmtNoSymbol(cartStore.grandTotal)}?`,
    confirmText: 'Proses Sekarang'
  })
  if (!result.isConfirmed) return

  // Populate checkout form
  checkoutForm.items = cartStore.items.map(item => ({
    product: { id: item.product.id },
    qty: item.qty,
    discount: item.discount,
    note: item.note
  }))
  checkoutForm.customer_id = cartStore.customer?.id || null
  checkoutForm.discount_type = cartStore.discountType
  checkoutForm.discount_value = cartStore.discountValue
  checkoutForm.tax_rate = cartStore.taxRate
  checkoutForm.payment_method = cartStore.paymentMethod
  checkoutForm.amount_paid = cartStore.amountPaid
  checkoutForm.note = cartStore.transactionNote

  checkoutForm.post('/pos', {
    onSuccess: () => {
      const receipt = page.props.flash?.receipt
      if (receipt) {
        receiptData.value = receipt
        showReceiptModal.value = true
      }
      showSuccess('Transaksi berhasil diproses!')
      cartStore.clearCart()
    },
    onError: (err) => {
      showError(Object.values(err)[0] || 'Gagal memproses transaksi.')
    }
  })
}

// Held/suspended transactions
const showHeldModal = ref(false)

function holdTx() {
  if (cartStore.items.length === 0) {
    showError('Keranjang kosong, tidak ada transaksi untuk di-hold.')
    return
  }
  const success = cartStore.holdCurrentTransaction()
  if (success) showSuccess('Transaksi berhasil di-hold.')
}

function resumeTx(id) {
  cartStore.resumeTransaction(id)
  showHeldModal.value = false
  showSuccess('Transaksi dilanjutkan.')
}

// Print thermal helper using silent hidden iframe
function printReceipt() {
  const printContent = document.getElementById('thermal-receipt-body').innerHTML
  const iframe = document.getElementById('receipt-print-iframe')
  if (!iframe) return

  const doc = iframe.contentWindow.document || iframe.contentDocument
  doc.open()
  doc.write(`
    <html>
      <head>
        <title>Cetak Struk</title>
        <style>
          @page {
            margin: 0;
            size: auto;
          }
          body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 11px;
            line-height: 1.3;
            margin: 8px;
            width: 280px;
            color: #000;
          }
          .text-center { text-align: center; }
          .text-right { text-align: right; }
          .divider { border-top: 1px dashed #000; margin: 6px 0; }
          .item-row { display: flex; justify-content: space-between; }
          .totals-row { display: flex; justify-content: space-between; font-weight: bold; }
          .mb-2 { margin-bottom: 6px; }
          .text-danger { color: #000 !important; }
        </style>
      </head>
      <body>
        ${printContent}
        <script>
          window.onload = function() {
            window.focus();
            window.print();
          };
        <\/script>
      </body>
    </html>
  `)
  doc.close()
}

// Hotkey listeners
function handleHotkeys(e) {
  if (e.key === 'F1') {
    e.preventDefault()
    document.getElementById('pos-search-input')?.focus()
  } else if (e.key === 'F2') {
    e.preventDefault()
    holdTx()
  } else if (e.key === 'F4') {
    e.preventDefault()
    processCheckout()
  } else if (e.key === 'F7') {
    e.preventDefault()
    cartStore.clearCart()
  }
}

onMounted(() => {
  window.addEventListener('keydown', handleHotkeys)
})
onUnmounted(() => {
  window.removeEventListener('keydown', handleHotkeys)
})

// Currency Helpers
const fmt = (v) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(v)
const fmtNoSymbol = (v) => new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(v)
</script>

<template>
  <div class="pos-page pos-layout p-1">
    <div class="row g-3">
      <!-- ── Bagian Kiri: Katalog Produk ── -->
      <div class="col-lg-7">
        <CCard class="h-100 border-0 shadow-sm">
          <CCardHeader class="bg-white border-bottom-0 py-3">
            <div class="row g-2 align-items-center">
              <div class="col-md-6">
                <h5 class="m-0 fw-bold text-primary">Katalog Produk</h5>
                <small class="text-body-secondary">Pilih/Scan produk untuk dimasukkan keranjang (F1)</small>
              </div>
              <div class="col-md-6">
                <input
                  id="pos-search-input"
                  v-model="searchQuery"
                  type="text"
                  class="form-control"
                  placeholder="Cari nama / scan SKU..."
                />
              </div>
            </div>

            <!-- Kategori Tabs -->
            <div class="d-flex gap-1 overflow-x-auto mt-3 pb-2 scrollbar-thin">
              <button
                class="btn btn-sm rounded-pill px-3 flex-shrink-0"
                :class="selectedCategoryId === '' ? 'btn-primary' : 'btn-outline-primary'"
                @click="selectedCategoryId = ''"
              >
                Semua Kategori
              </button>
              <button
                v-for="cat in categories"
                :key="cat.id"
                class="btn btn-sm rounded-pill px-3 flex-shrink-0"
                :class="selectedCategoryId === cat.id ? 'btn-primary' : 'btn-outline-primary'"
                @click="selectedCategoryId = cat.id"
              >
                {{ cat.name }}
              </button>
            </div>
          </CCardHeader>

          <CCardBody class="bg-light p-3" style="max-height: 620px; overflow-y: auto;">
            <!-- Katalog Grid -->
            <div v-if="filteredProducts.length > 0" class="row g-2">
              <div
                v-for="p in filteredProducts"
                :key="p.id"
                class="col-6 col-sm-4 col-md-3 col-xl-2"
              >
                <div
                  class="card product-pos-card h-100 border-0 shadow-sm cursor-pointer position-relative overflow-hidden"
                  @click="addToCart(p)"
                >
                  <!-- Stock alert badge -->
                  <span
                    class="badge position-absolute top-0 start-0 m-1"
                    :class="p.stock <= p.min_stock ? 'bg-danger' : 'bg-success'"
                    style="z-index: 1;"
                  >
                    Stok: {{ p.stock }}
                  </span>

                  <div class="ratio ratio-4x3 bg-light d-flex align-items-center justify-content-center">
                    <img
                      v-if="p.image"
                      :src="`/storage/${p.image}`"
                      class="card-img-top object-fit-cover"
                      :alt="p.name"
                    />
                    <div v-else class="fs-1 opacity-25">📦</div>
                  </div>

                  <div class="card-body p-2 d-flex flex-column justify-content-between">
                    <div class="product-title-line fw-semibold text-dark small" :title="p.name">
                      {{ p.name }}
                    </div>
                    <div class="d-flex align-items-center justify-content-between mt-1">
                      <span class="text-success fw-bold small">{{ fmt(p.selling_price) }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-5 text-body-secondary">
              <div class="fs-1 opacity-25">📭</div>
              <div class="mt-2 fw-semibold">Produk tidak ditemukan</div>
              <small>Coba kata kunci lain atau pilih kategori berbeda.</small>
            </div>
          </CCardBody>
        </CCard>
      </div>

      <!-- ── Bagian Kanan: Keranjang Belanja & Checkout ── -->
      <div class="col-lg-5">
        <CCard class="h-100 border-0 shadow-sm">
          <CCardHeader class="bg-white py-3 d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
              <h5 class="m-0 fw-bold text-dark">Keranjang</h5>
              <span class="badge bg-primary rounded-pill">{{ cartStore.totalQty }} item</span>
            </div>
            <!-- Suspend / resume buttons -->
            <div class="d-flex gap-1">
              <button
                class="btn btn-outline-secondary btn-sm"
                title="Hold Transaksi (F2)"
                @click="holdTx"
              >
                ⏸ Hold
              </button>
              <button
                class="btn btn-outline-primary btn-sm position-relative"
                title="Daftar Transaksi Held"
                @click="showHeldModal = true"
              >
                📂 Held List
                <span
                  v-if="cartStore.heldTransactions.length > 0"
                  class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                >
                  {{ cartStore.heldTransactions.length }}
                </span>
              </button>
            </div>
          </CCardHeader>

          <!-- List Belanja -->
          <div class="scrollbar-thin px-3 py-2 flex-grow-1" style="max-height: 280px; overflow-y: auto; background:#fafafa;">
            <div v-if="cartStore.items.length > 0" class="d-flex flex-column gap-2">
              <div
                v-for="item in cartStore.items"
                :key="item.product.id"
                class="bg-white p-2 rounded border border-light shadow-sm position-relative"
              >
                <!-- Remove item button -->
                <button
                  type="button"
                  class="btn-close position-absolute top-0 end-0 m-2"
                  style="font-size:10px"
                  @click="cartStore.removeItem(item.product.id)"
                />

                <div class="d-flex align-items-start gap-2 pe-4">
                  <div class="fw-semibold small flex-grow-1 text-dark">{{ item.product.name }}</div>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-2">
                  <span class="text-body-secondary small">{{ fmt(item.product.selling_price) }}</span>
                  <!-- Qty input controls -->
                  <div class="input-group input-group-sm" style="width: 100px;">
                    <button
                      class="btn btn-outline-secondary"
                      type="button"
                      @click="handleQtyChange(item.product.id, item.qty - 1)"
                    >-</button>
                    <input
                      type="number"
                      class="form-control text-center ps-1 pe-1"
                      :value="item.qty"
                      min="1"
                      :max="item.product.stock"
                      @input="handleQtyChange(item.product.id, $event.target.value)"
                    />
                    <button
                      class="btn btn-outline-secondary"
                      type="button"
                      @click="handleQtyChange(item.product.id, item.qty + 1)"
                    >+</button>
                  </div>
                </div>

                <!-- Custom Discount & Note per Item -->
                <div class="row g-1 mt-2">
                  <div class="col-6">
                    <input
                      type="number"
                      class="form-control form-control-sm"
                      placeholder="Diskon item (Rp)"
                      :value="item.discount"
                      @input="cartStore.updateItemDiscount(item.product.id, $event.target.value)"
                    />
                  </div>
                  <div class="col-6">
                    <input
                      type="text"
                      class="form-control form-control-sm"
                      placeholder="Catatan..."
                      v-model="item.note"
                      @input="cartStore.updateItemNote(item.product.id, item.note)"
                    />
                  </div>
                </div>
              </div>
            </div>

            <div v-else class="text-center py-5 text-body-secondary">
              <div class="fs-1 opacity-25">🛒</div>
              <small class="d-block mt-2 fw-semibold text-secondary">Keranjang Belanja Kosong</small>
            </div>
          </div>

          <!-- Checkout & Kalkulasi Footer -->
          <div class="p-3 border-top bg-white">
            <div class="row g-2 mb-2">
              <div class="col-md-6">
                <!-- Customer Selection -->
                <BaseSelect
                  v-model="selectedCustomer"
                  label="Customer / Member"
                  :options="customers"
                  placeholder="Pilih Customer Umum"
                />
              </div>
              <div class="col-md-3">
                <BaseInput
                  label="Pajak (%)"
                  type="number"
                  :model-value="cartStore.taxRate"
                  @update:model-value="cartStore.setTaxRate($event)"
                />
              </div>
              <div class="col-md-3">
                <BaseInput
                  label="Diskon Transaksi"
                  type="number"
                  :model-value="cartStore.discountValue"
                  @update:model-value="cartStore.setDiscount(cartStore.discountType, $event)"
                />
              </div>
            </div>

            <!-- Details Block -->
            <div class="bg-light p-3 rounded mb-3">
              <div class="d-flex justify-content-between mb-1 small text-secondary">
                <span>Subtotal</span>
                <span>{{ fmt(cartStore.itemSubtotal) }}</span>
              </div>
              <div class="d-flex justify-content-between mb-1 small text-danger">
                <span>Diskon Transaksi</span>
                <span>- {{ fmt(cartStore.transactionDiscountAmount) }}</span>
              </div>
              <div class="d-flex justify-content-between mb-1 small text-secondary">
                <span>Pajak ({{ cartStore.taxRate }}%)</span>
                <span>{{ fmt(cartStore.taxAmount) }}</span>
              </div>
              <hr class="my-2" />
              <div class="d-flex justify-content-between align-items-center">
                <h5 class="fw-bold text-dark m-0">Grand Total</h5>
                <h4 class="fw-bold text-primary m-0">{{ fmt(cartStore.grandTotal) }}</h4>
              </div>
            </div>

            <!-- Payment Details -->
            <div class="mb-3">
              <label class="form-label fw-semibold">Metode Pembayaran</label>
              <div class="d-flex gap-1">
                <button
                  v-for="method in ['cash', 'transfer', 'qris', 'e-wallet']"
                  :key="method"
                  class="btn btn-sm flex-fill"
                  :class="cartStore.paymentMethod === method ? 'btn-primary' : 'btn-outline-primary'"
                  @click="cartStore.setPaymentMethod(method)"
                >
                  {{ method.toUpperCase() }}
                </button>
              </div>
            </div>

            <!-- Cash Input Panel -->
            <div v-if="cartStore.paymentMethod === 'cash'" class="mb-3">
              <div class="row g-2 mb-2">
                <div class="col-8">
                  <BaseInput
                    label="Jumlah Bayar (Rp)"
                    type="number"
                    :model-value="cartStore.amountPaid"
                    @update:model-value="cartStore.setAmountPaid($event)"
                  />
                </div>
                <div class="col-4 d-flex align-items-end">
                  <button
                    class="btn btn-outline-success btn-sm w-100 mb-3"
                    style="height: 38px;"
                    @click="setQuickCash('pas')"
                  >Uang Pas</button>
                </div>
              </div>

              <!-- Quick cash buttons -->
              <div class="d-flex flex-wrap gap-1">
                <button
                  v-for="amt in quickCashAmounts"
                  :key="amt"
                  class="btn btn-outline-secondary btn-sm flex-fill"
                  @click="setQuickCash(amt)"
                >
                  {{ fmtNoSymbol(amt) }}
                </button>
              </div>

              <!-- Change feedback -->
              <div class="mt-3 p-2 bg-success bg-opacity-10 border border-success rounded text-success d-flex justify-content-between align-items-center">
                <span class="small fw-semibold">Kembalian:</span>
                <span class="fw-bold fs-5">{{ fmt(cartStore.changeAmount) }}</span>
              </div>
            </div>

            <!-- Checkout Action Buttons -->
            <div class="row g-2 mt-2">
              <div class="col-4">
                <BaseButton
                  variant="outline-danger"
                  block
                  title="Kosongkan Keranjang (F7)"
                  @click="cartStore.clearCart"
                >
                  Reset
                </BaseButton>
              </div>
              <div class="col-8">
                <BaseButton
                  variant="success"
                  block
                  :loading="checkoutForm.processing"
                  title="Bayar & Checkout (F4)"
                  @click="processCheckout"
                >
                  💵 Proses Pembayaran (F4)
                </BaseButton>
              </div>
            </div>
          </div>
        </CCard>
      </div>
    </div>

    <!-- Modal Held Transactions -->
    <BaseModal
      :show="showHeldModal"
      title="Daftar Transaksi Ditangguhkan (Hold)"
      :show-footer="false"
      @close="showHeldModal = false"
    >
      <div v-if="cartStore.heldTransactions.length > 0" class="list-group">
        <div
          v-for="tx in cartStore.heldTransactions"
          :key="tx.id"
          class="list-group-item d-flex justify-content-between align-items-center"
        >
          <div>
            <div class="fw-bold">Pukul {{ tx.time }} ({{ tx.items.reduce((s,i)=>s+i.qty,0) }} item)</div>
            <small class="text-secondary">Customer: {{ tx.customer?.name ?? 'Umum' }}</small>
          </div>
          <div class="d-flex gap-1">
            <button class="btn btn-primary btn-sm" @click="resumeTx(tx.id)">Lanjutkan</button>
            <button class="btn btn-outline-danger btn-sm" @click="cartStore.deleteHeldTransaction(tx.id)">Hapus</button>
          </div>
        </div>
      </div>
      <div v-else class="text-center py-4 text-body-secondary">
        Tidak ada transaksi yang ditangguhkan.
      </div>
    </BaseModal>

    <!-- Thermal Receipt Modal -->
    <BaseModal
      :show="showReceiptModal"
      title="Struk Penjualan"
      confirm-text="Print Struk"
      confirm-variant="success"
      @close="showReceiptModal = false"
      @confirm="printReceipt"
    >
      <div id="thermal-receipt" class="d-flex justify-content-center p-3 bg-white border rounded">
        <div id="thermal-receipt-body" style="font-family: 'Courier New', Courier, monospace; width: 300px; font-size:12px; color:#000;">
          <div class="text-center">
            <h4 style="margin: 0 0 5px 0; font-weight:bold;">POS KASIR PREMIUM</h4>
            <div style="font-size:10px;">Jl. Raya Utama No. 88, Jakarta</div>
            <div style="font-size:10px;">Telp: 0812-3456-7890</div>
            <div class="divider">--------------------------------</div>
          </div>

          <div style="font-size:11px;">
            <div class="item-row"><span>No Invoice :</span> <span>{{ receiptData?.invoice_no }}</span></div>
            <div class="item-row"><span>Tanggal    :</span> <span>{{ receiptData?.created_at }}</span></div>
            <div class="item-row"><span>Kasir      :</span> <span>{{ receiptData?.cashier }}</span></div>
            <div class="item-row"><span>Customer   :</span> <span>{{ receiptData?.customer }}</span></div>
          </div>
          <div class="divider">--------------------------------</div>

          <div style="font-size:11px;">
            <div v-for="(it, idx) in receiptData?.items" :key="idx" class="mb-2">
              <div class="fw-bold">{{ it.name }}</div>
              <div class="item-row">
                <span>{{ it.qty }} x {{ fmtNoSymbol(it.price) }}</span>
                <span>{{ fmt(it.subtotal) }}</span>
              </div>
              <div v-if="it.discount > 0" class="item-row text-danger" style="font-size:10px;">
                <span>   Diskon Item</span>
                <span>-{{ fmt(it.discount) }}</span>
              </div>
            </div>
          </div>
          <div class="divider">--------------------------------</div>

          <div style="font-size:11px;">
            <div class="item-row"><span>Subtotal:</span> <span>{{ fmt(receiptData?.subtotal) }}</span></div>
            <div class="item-row text-danger"><span>Diskon:</span> <span>-{{ fmt(receiptData?.discount) }}</span></div>
            <div class="item-row"><span>Pajak:</span> <span>{{ fmt(receiptData?.tax) }}</span></div>
            <div class="divider">--------------------------------</div>
            <div class="totals-row fs-6"><span>Grand Total:</span> <span>{{ fmt(receiptData?.grand_total) }}</span></div>
            <div class="item-row"><span>Metode Bayar:</span> <span>{{ receiptData?.payment_method }}</span></div>
            <div class="item-row"><span>Bayar:</span> <span>{{ fmt(receiptData?.amount_paid) }}</span></div>
            <div class="item-row"><span>Kembali:</span> <span>{{ fmt(receiptData?.change) }}</span></div>
          </div>
          <div class="divider">--------------------------------</div>

          <div class="text-center" style="font-size: 10px; margin-top:15px;">
            <div>TERIMA KASIH ATAS KUNJUNGAN ANDA</div>
            <div>Barang yang sudah dibeli tidak dapat ditukar</div>
          </div>
        </div>
      </div>
    </BaseModal>

    <!-- Hidden iframe for silent printing -->
    <iframe id="receipt-print-iframe" style="position: absolute; width: 0; height: 0; border: 0;" src="about:blank"></iframe>
  </div>
</template>

<!-- Styling has been moved to global resources/scss/style.scss for full structured layout -->
