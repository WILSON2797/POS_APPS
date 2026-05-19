<script setup>
import { ref, computed, onMounted, nextTick } from "vue";
import { useForm, router } from "@inertiajs/vue3";
import DefaultLayout from "@/Layouts/DefaultLayout.vue";
import BaseInput from "@/Components/Base/BaseInput.vue";
import BaseSelect from "@/Components/Base/BaseSelect.vue";
import BaseTextarea from "@/Components/Base/BaseTextarea.vue";
import BaseButton from "@/Components/Base/BaseButton.vue";
import BaseDatePicker from "@/Components/Base/BaseDatePicker.vue";
import { useSwal } from "@/composables/useSwal";

defineOptions({ layout: DefaultLayout });

const props = defineProps({
    suppliers: Array,
    products: Array,
});

const { showSuccess, showError } = useSwal();

// Form State
const form = useForm({
    supplier_id: "",
    receipt_date: new Date().toISOString().split("T")[0],
    reference_no: "",
    note: "",
    items: [], // Array of { product_id, name, barcode, qty, cost_price, unit }
});

// Product Search State
const searchQuery = ref("");
const showSuggestions = ref(false);

const filteredProducts = computed(() => {
    if (!searchQuery.value) return [];
    const q = searchQuery.value.toLowerCase();
    return props.products
        .filter(
            (p) =>
                (p.name && p.name.toLowerCase().includes(q)) ||
                (p.barcode && p.barcode.toLowerCase().includes(q)),
        )
        .slice(0, 8);
});

// Web Audio API beep sound for satisfying hardware scanner feedback
function playBeepSound() {
    try {
        const context = new (
            window.AudioContext || window.webkitAudioContext
        )();
        const oscillator = context.createOscillator();
        const gainNode = context.createGain();
        oscillator.connect(gainNode);
        gainNode.connect(context.destination);
        oscillator.type = "sine";
        oscillator.frequency.value = 1000;
        gainNode.gain.setValueAtTime(0.08, context.currentTime);
        oscillator.start();
        oscillator.stop(context.currentTime + 0.08);
    } catch (e) {
        console.error(e);
    }
}

// Add Product to list
function addProduct(product) {
    const existingIndex = form.items.findIndex(
        (item) => item.product_id === product.id,
    );

    if (existingIndex > -1) {
        form.items[existingIndex].qty += 1;
    } else {
        form.items.push({
            product_id: product.id,
            name: product.name,
            barcode: product.barcode,
            qty: 1,
            cost_price: parseFloat(product.cost_price) || 0,
            unit: product.unit || "pcs",
        });
    }

    playBeepSound();
    searchQuery.value = "";
    showSuggestions.value = false;
    focusSearchInput();
}

// Handle Barcode Scanner Enter Key
function handleBarcodeSearch() {
    if (!searchQuery.value) return;

    // Direct barcode match
    const exactMatch = props.products.find(
        (p) =>
            p.barcode &&
            p.barcode.toLowerCase() === searchQuery.value.toLowerCase(),
    );

    if (exactMatch) {
        addProduct(exactMatch);
        return;
    }

    // If suggestions contain matches, pick the first one
    if (filteredProducts.value.length > 0) {
        addProduct(filteredProducts.value[0]);
    } else {
        showError("Produk tidak ditemukan.");
        searchQuery.value = "";
    }
}

// Remove item
function removeItem(index) {
    form.items.splice(index, 1);
}

// Totals
const totalItems = computed(() => form.items.length);
const totalQty = computed(() =>
    form.items.reduce((sum, item) => sum + parseInt(item.qty || 0), 0),
);
const grandTotal = computed(() =>
    form.items.reduce(
        (sum, item) =>
            sum + parseFloat(item.cost_price || 0) * parseInt(item.qty || 0),
        0,
    ),
);

// Focus Search
function focusSearchInput() {
    nextTick(() => {
        document.getElementById("gr-search-input")?.focus();
    });
}

onMounted(() => {
    focusSearchInput();
});

// Format currency
const fmt = (v) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        maximumFractionDigits: 0,
    }).format(v);

// Format raw number to Indonesian/thousands dot separator
const formatNumber = (val) => {
    if (val === undefined || val === null || val === "") return "";
    // Strip everything except digits
    const num = parseFloat(val.toString().replace(/[^0-9.-]/g, ""));
    if (isNaN(num)) return "";
    return new Intl.NumberFormat("id-ID").format(num);
};

// Update state when user types in formatted input
const updateQty = (item, rawValue) => {
    // Strip dots/thousands separator and parse to integer
    const cleanValue = rawValue.replace(/[^0-9]/g, "");
    const parsed = parseInt(cleanValue, 10);
    item.qty = isNaN(parsed) ? 1 : parsed;
};

// Submit form
function submitReceipt() {
    if (!form.supplier_id) {
        showError("Silakan pilih supplier terlebih dahulu.");
        return;
    }
    if (form.items.length === 0) {
        showError("Silakan tambahkan minimal 1 produk.");
        return;
    }

    form.post("/goods-receipts", {
        onSuccess: () => showSuccess("Penerimaan barang berhasil disimpan."),
        onError: () =>
            showError("Gagal menyimpan penerimaan barang. Periksa input."),
    });
}
</script>

<template>
    <div class="container-fluid px-3">
        <!-- Header Page -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-0 fw-bold text-dark">
                    Penerimaan Barang Baru (Restok)
                </h4>
                <small class="text-muted">Catat barang masuk.</small>
            </div>
            <BaseButton
                class="btn-primary text-white px-4 d-inline-flex align-items-center gap-1 shadow-sm"
                style="border-radius: 8px"
                @click="router.visit('/goods-receipts')"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="16"
                    height="16"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2.5"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                Kembali ke Riwayat
            </BaseButton>
        </div>

        <div class="row g-4">
            <!-- Left Side: Transaction Header Form -->
            <div class="col-lg-4">
                <CCard class="shadow-sm border-0 h-lg-100">
                    <CCardHeader class="bg-white border-0 py-3">
                        <strong class="text-dark fs-5"
                            >Informasi Pengiriman</strong
                        >
                    </CCardHeader>
                    <CCardBody>
                        <!-- Supplier -->
                        <div class="mb-3">
                            <BaseSelect
                                v-model="form.supplier_id"
                                label="Supplier Pengirim"
                                :options="suppliers"
                                :error="form.errors.supplier_id"
                                required
                            />
                        </div>

                        <!-- Date Receipt -->
                        <div class="mb-3">
                            <BaseDatePicker
                                v-model="form.receipt_date"
                                label="Tanggal Penerimaan"
                                :error="form.errors.receipt_date"
                                required
                            />
                        </div>

                        <!-- Reference No -->
                        <div class="mb-3">
                            <BaseInput
                                v-model="form.reference_no"
                                label="No. Surat Jalan / Invoice Supplier"
                                placeholder="Contoh: SJ/12345/V/2026"
                                :error="form.errors.reference_no"
                            />
                        </div>

                        <!-- Catatan -->
                        <div class="mb-3">
                            <BaseTextarea
                                v-model="form.note"
                                label="Catatan Penerimaan"
                                placeholder="Masukkan catatan tambahan..."
                                :rows="3"
                                :error="form.errors.note"
                            />
                        </div>
                    </CCardBody>
                </CCard>
            </div>

            <!-- Right Side: Interactive Scan & Items List Table -->
            <div class="col-lg-8">
                <div class="d-flex flex-column gap-4 h-lg-100">
                    <!-- Scanner and Search Bar -->
                    <CCard class="shadow-sm border-0 gr-search-card">
                        <CCardBody
                            class="position-relative py-3 gr-search-card-body"
                        >
                            <div class="d-flex gap-2 align-items-center">
                                <div class="flex-grow-1 position-relative">
                                    <div class="input-group">
                                        <span
                                            class="input-group-text bg-white border-end-0"
                                        >
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                width="18"
                                                height="18"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2.5"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                class="text-muted"
                                            >
                                                <circle
                                                    cx="11"
                                                    cy="11"
                                                    r="8"
                                                ></circle>
                                                <line
                                                    x1="21"
                                                    y1="21"
                                                    x2="16.65"
                                                    y2="16.65"
                                                ></line>
                                            </svg>
                                        </span>
                                        <input
                                            id="gr-search-input"
                                            v-model="searchQuery"
                                            type="text"
                                            class="form-control border-start-0 ps-0 shadow-none py-2 fs-5"
                                            placeholder="Scan barcode produk atau cari nama produk di sini..."
                                            autocomplete="off"
                                            @keydown.enter.prevent="
                                                handleBarcodeSearch
                                            "
                                            @focus="showSuggestions = true"
                                            @blur="
                                                setTimeout(
                                                    () =>
                                                        (showSuggestions = false),
                                                    200,
                                                )
                                            "
                                        />
                                    </div>

                                    <!-- Search Autocomplete Suggestions List -->
                                    <div
                                        v-if="
                                            showSuggestions &&
                                            filteredProducts.length > 0
                                        "
                                        class="position-absolute w-100 border rounded mt-1 gr-suggestions-list"
                                    >
                                        <div
                                            v-for="p in filteredProducts"
                                            :key="p.id"
                                            class="p-3 border-bottom d-flex justify-content-between align-items-center cursor-pointer list-suggestion-item"
                                            style="cursor: pointer"
                                            @mousedown="addProduct(p)"
                                        >
                                            <div>
                                                <div
                                                    class="fw-semibold text-dark"
                                                >
                                                    {{ p.name }}
                                                </div>
                                                <small
                                                    class="text-muted font-monospace fs-12"
                                                    >{{
                                                        p.barcode ||
                                                        "Tanpa Barcode"
                                                    }}</small
                                                >
                                            </div>
                                            <div class="text-end">
                                                <span
                                                    class="badge bg-secondary mb-1"
                                                    >Stok: {{ p.stock }}
                                                    {{ p.unit }}</span
                                                >
                                                <div
                                                    class="text-muted font-monospace fs-12"
                                                >
                                                    Modal:
                                                    {{ fmt(p.cost_price) }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CCardBody>
                    </CCard>

                    <!-- Items List Table -->
                    <CCard class="shadow-sm border-0 flex-grow-1">
                        <CCardHeader
                            class="bg-white border-0 py-3 d-flex justify-content-between align-items-center"
                        >
                            <strong class="text-dark fs-5"
                                >📦 Daftar Produk Masuk</strong
                            >
                            <span
                                class="badge bg-primary px-3 py-2 fs-7"
                                style="border-radius: 6px"
                                >{{ totalItems }} Item Unik</span
                            >
                        </CCardHeader>
                        <CCardBody class="p-0">
                            <!-- Desktop/Tablet Table View (Hidden on mobile) -->
                            <div
                                class="d-none d-md-block table-responsive"
                                style="max-height: 400px; overflow-y: auto"
                            >
                                <table
                                    class="table table-hover align-middle mb-0"
                                >
                                    <thead
                                        class="table-light sticky-top bg-light"
                                    >
                                        <tr>
                                            <th class="border-0 ps-3">
                                                Produk / Barcode
                                            </th>
                                            <th
                                                class="border-0 text-center"
                                                style="width: 220px"
                                            >
                                                Qty (Masuk)
                                            </th>
                                            <th
                                                class="border-0 text-end"
                                                style="width: 180px"
                                            >
                                                Harga Modal/PCS
                                            </th>
                                            <th
                                                class="border-0 text-end"
                                                style="width: 180px"
                                            >
                                                Subtotal (Rp)
                                            </th>
                                            <th
                                                class="border-0 text-center"
                                                style="width: 60px"
                                            >
                                                Aksi
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="(item, idx) in form.items"
                                            :key="item.product_id"
                                        >
                                            <td class="ps-3">
                                                <div
                                                    class="fw-semibold text-dark"
                                                >
                                                    {{ item.name }}
                                                </div>
                                                <small
                                                    class="text-muted font-monospace fs-11"
                                                    >{{
                                                        item.barcode || "—"
                                                    }}</small
                                                >
                                            </td>
                                            <td>
                                                <div
                                                    class="input-group input-group-sm justify-content-center"
                                                >
                                                    <button
                                                        type="button"
                                                        class="btn btn-outline-secondary px-2"
                                                        :disabled="
                                                            item.qty <= 1
                                                        "
                                                        @click="item.qty--"
                                                    >
                                                        -
                                                    </button>
                                                    <input
                                                        :value="
                                                            formatNumber(
                                                                item.qty,
                                                            )
                                                        "
                                                        @input="
                                                            updateQty(
                                                                item,
                                                                $event.target
                                                                    .value,
                                                            )
                                                        "
                                                        type="text"
                                                        class="form-control text-center shadow-none font-monospace fw-semibold"
                                                        style="max-width: 120px"
                                                        required
                                                    />
                                                    <button
                                                        type="button"
                                                        class="btn btn-outline-secondary px-2"
                                                        @click="item.qty++"
                                                    >
                                                        +
                                                    </button>
                                                </div>
                                            </td>
                                            <td>
                                                <input
                                                    v-model.number="
                                                        item.cost_price
                                                    "
                                                    type="number"
                                                    min="0"
                                                    class="form-control form-control-sm text-end font-monospace shadow-none"
                                                    required
                                                />
                                            </td>
                                            <td
                                                class="text-end fw-semibold text-dark pe-3 font-monospace"
                                            >
                                                {{
                                                    fmt(
                                                        item.qty *
                                                            item.cost_price,
                                                    )
                                                }}
                                            </td>
                                            <td class="text-center">
                                                <button
                                                    type="button"
                                                    class="btn btn-link text-danger p-0 border-0"
                                                    title="Hapus"
                                                    @click="removeItem(idx)"
                                                >
                                                    <svg
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        width="18"
                                                        height="18"
                                                        viewBox="0 0 24 24"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        stroke-width="2.5"
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                    >
                                                        <polyline
                                                            points="3 6 5 6 21 6"
                                                        ></polyline>
                                                        <path
                                                            d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"
                                                        ></path>
                                                    </svg>
                                                </button>
                                            </td>
                                        </tr>
                                        <tr v-if="form.items.length === 0">
                                            <td
                                                colspan="5"
                                                class="text-center py-5 text-muted"
                                            >
                                                <div class="fs-2 mb-2">📥</div>
                                                <div>
                                                    Belum ada produk terpilih.
                                                </div>
                                                <small
                                                    >Scan barcode atau cari nama
                                                    produk di atas untuk
                                                    memasukkan stok.</small
                                                >
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Mobile Card List View (Visible only on mobile) -->
                            <div
                                class="d-block d-md-none p-3"
                                style="max-height: 420px; overflow-y: auto"
                            >
                                <div
                                    v-if="form.items.length === 0"
                                    class="text-center py-5 text-muted"
                                >
                                    <div class="fs-2 mb-2">📥</div>
                                    <div>Belum ada produk terpilih.</div>
                                    <small
                                        >Scan barcode atau cari nama produk di
                                        atas untuk memasukkan stok.</small
                                    >
                                </div>
                                <div
                                    v-for="(item, idx) in form.items"
                                    :key="'mob-' + item.product_id"
                                    class="gr-mobile-item-card"
                                >
                                    <div
                                        class="d-flex justify-content-between align-items-start"
                                    >
                                        <div>
                                            <div class="item-title">
                                                {{ item.name }}
                                            </div>
                                            <div class="item-barcode">
                                                {{
                                                    item.barcode ||
                                                    "Tanpa Barcode"
                                                }}
                                            </div>
                                        </div>
                                        <button
                                            type="button"
                                            class="btn btn-outline-danger btn-sm p-2 border-0"
                                            title="Hapus"
                                            @click="removeItem(idx)"
                                        >
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                width="18"
                                                height="18"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2.5"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                            >
                                                <polyline
                                                    points="3 6 5 6 21 6"
                                                ></polyline>
                                                <path
                                                    d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"
                                                ></path>
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="item-controls-grid">
                                        <div>
                                            <div class="item-control-label">
                                                Qty Masuk
                                            </div>
                                            <div
                                                class="input-group input-group-sm"
                                            >
                                                <button
                                                    type="button"
                                                    class="btn btn-outline-secondary px-2"
                                                    :disabled="item.qty <= 1"
                                                    @click="item.qty--"
                                                >
                                                    -
                                                </button>
                                                <input
                                                    :value="
                                                        formatNumber(item.qty)
                                                    "
                                                    @input="
                                                        updateQty(
                                                            item,
                                                            $event.target.value,
                                                        )
                                                    "
                                                    type="text"
                                                    class="form-control text-center shadow-none font-monospace fw-semibold"
                                                    required
                                                />
                                                <button
                                                    type="button"
                                                    class="btn btn-outline-secondary px-2"
                                                    @click="item.qty++"
                                                >
                                                    +
                                                </button>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="item-control-label">
                                                Modal Satuan (Rp)
                                            </div>
                                            <input
                                                v-model.number="item.cost_price"
                                                type="number"
                                                min="0"
                                                class="form-control form-control-sm text-end font-monospace shadow-none"
                                                required
                                            />
                                        </div>
                                        <div class="item-subtotal-box">
                                            <span
                                                class="text-muted small fw-semibold"
                                                >SUBTOTAL:</span
                                            >
                                            <span
                                                class="fw-bold text-dark font-monospace"
                                                >{{
                                                    fmt(
                                                        item.qty *
                                                            item.cost_price,
                                                    )
                                                }}</span
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CCardBody>

                        <!-- Summary and Action Footer inside Card -->
                        <CCardFooter class="bg-white border-top py-3 px-4">
                            <div class="row align-items-center g-3">
                                <div class="col-md-7 d-flex gap-4">
                                    <div>
                                        <small class="text-muted d-block"
                                            >TOTAL QUANTITY</small
                                        >
                                        <strong class="fs-4 text-dark"
                                            >{{ totalQty }} unit</strong
                                        >
                                    </div>
                                    <div class="border-end h-30px"></div>
                                    <div>
                                        <small class="text-muted d-block"
                                            >GRAND TOTAL HARGA MODAL</small
                                        >
                                        <strong
                                            class="fs-4 text-danger font-monospace"
                                            >{{ fmt(grandTotal) }}</strong
                                        >
                                    </div>
                                </div>
                                <div class="col-md-5 text-end">
                                    <BaseButton
                                        class="btn-primary px-5 py-2 fs-6 shadow-sm w-100 w-md-auto"
                                        style="
                                            border-radius: 8px;
                                            font-weight: 600;
                                        "
                                        :loading="form.processing"
                                        @click="submitReceipt"
                                    >
                                        💾 Simpan Penerimaan
                                    </BaseButton>
                                </div>
                            </div>
                        </CCardFooter>
                    </CCard>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.list-suggestion-item:hover {
    background-color: #f8f9fa;
}
.h-30px {
    height: 35px;
    align-self: center;
}
</style>
