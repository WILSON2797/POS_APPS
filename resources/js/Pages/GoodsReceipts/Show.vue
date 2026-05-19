<script setup>
import { router } from "@inertiajs/vue3";
import DefaultLayout from "@/Layouts/DefaultLayout.vue";
import BaseButton from "@/Components/Base/BaseButton.vue";

defineOptions({ layout: DefaultLayout });

const props = defineProps({
    receipt: Object,
});

const fmt = (v) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        maximumFractionDigits: 0,
    }).format(v);

const fmtDate = (d) => {
    if (!d) return "-";
    return new Date(d).toLocaleDateString("id-ID", {
        day: "numeric",
        month: "long",
        year: "numeric",
    });
};

function triggerPrint() {
    window.print();
}
</script>

<template>
    <div class="container-fluid px-3">
        <!-- Action bar - hidden when printing -->
        <div
            class="d-flex justify-content-between align-items-center mb-4 d-print-none"
        >
            <div>
                <h4 class="mb-0 fw-bold text-dark">Detail Penerimaan Barang</h4>
                <small class="text-muted"
                    >Lihat rincian penerimaan stok kulakan dari supplier</small
                >
            </div>
            <div class="d-flex gap-2">
                <BaseButton
                    class="btn-primary text-white d-inline-flex align-items-center gap-1 shadow-sm"
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
                    Kembali
                </BaseButton>
                <button
                    class="btn btn-primary text-white d-inline-flex align-items-center gap-2 px-4 shadow-sm"
                    style="border-radius: 8px; font-weight: 600"
                    @click="triggerPrint"
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
                        <polyline points="6 9 6 2 18 2 18 9"></polyline>
                        <path
                            d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"
                        ></path>
                        <rect x="6" y="14" width="12" height="8"></rect>
                    </svg>
                    Cetak Nota Masuk
                </button>
            </div>
        </div>

        <!-- Printable Invoice Area -->
        <CCard id="print-area" class="shadow-sm border-0 mb-4 p-4">
            <CCardBody>
                <!-- Invoice Header -->
                <div class="row border-bottom pb-4 mb-4">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <h3 class="fw-bold text-primary mb-0">POS APPS</h3>
                        </div>
                        <p class="text-muted mb-0">
                            Sistem Inventory & Ritel Pintar
                        </p>
                        <p class="text-muted mb-0">
                            Halaman Manajemen Penerimaan Barang
                        </p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <h4 class="fw-bold text-dark mb-1">NOTA PENERIMAAN</h4>
                        <h5 class="font-monospace text-primary fw-bold mb-2">
                            {{ receipt.receipt_no }}
                        </h5>
                        <div class="text-muted">
                            Tanggal Terima:
                            <strong>{{ fmtDate(receipt.receipt_date) }}</strong>
                        </div>
                    </div>
                </div>

                <!-- Supplier and Officer Details -->
                <div class="row mb-5">
                    <!-- Supplier Information -->
                    <div class="col-md-6 mb-4 mb-md-0">
                        <span
                            class="text-muted text-uppercase d-block mb-2 fw-semibold fs-11"
                            >DITERIMA DARI SUPPLIER:</span
                        >
                        <h5 class="fw-bold text-dark mb-1">
                            {{ receipt.supplier?.name || "—" }}
                        </h5>
                        <p
                            class="mb-1 text-secondary"
                            v-if="receipt.supplier?.phone"
                        >
                            📞 {{ receipt.supplier.phone }}
                        </p>
                        <p
                            class="text-secondary mb-0"
                            v-if="receipt.supplier?.address"
                        >
                            📍 {{ receipt.supplier.address }}
                        </p>
                    </div>

                    <!-- Receipt Information -->
                    <div class="col-md-6 text-md-end">
                        <span
                            class="text-muted text-uppercase d-block mb-2 fw-semibold fs-11"
                            >INFORMASI PENERIMAAN:</span
                        >
                        <div class="mb-1 text-secondary">
                            Petugas Penerima:
                            <strong class="text-dark">{{
                                receipt.user?.name || "—"
                            }}</strong>
                        </div>
                        <div class="mb-1 text-secondary">
                            Nomor Surat Jalan:
                            <strong class="text-dark font-monospace">{{
                                receipt.reference_no || "—"
                            }}</strong>
                        </div>
                        <div class="text-secondary">
                            Waktu Input:
                            <strong class="text-dark">{{
                                new Date(receipt.created_at).toLocaleString(
                                    "id-ID",
                                )
                            }}</strong>
                        </div>
                    </div>
                </div>

                <!-- Detail Items Table -->
                <h5 class="fw-bold text-dark mb-3">
                    📦 Daftar Produk Diterima
                </h5>
                <div class="table-responsive mb-4">
                    <table
                        class="table table-bordered table-hover align-middle mb-0"
                    >
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width: 50px">
                                    #
                                </th>
                                <th>Produk / Barcode</th>
                                <th class="text-center" style="width: 120px">
                                    Satuan
                                </th>
                                <th class="text-center" style="width: 140px">
                                    Jumlah Masuk
                                </th>
                                <th class="text-end" style="width: 180px">
                                    Harga Modal Satuan (Rp)
                                </th>
                                <th class="text-end" style="width: 200px">
                                    Subtotal (Rp)
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(item, idx) in receipt.details"
                                :key="item.id"
                            >
                                <td class="text-center fw-semibold text-muted">
                                    {{ idx + 1 }}
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">
                                        {{
                                            item.product?.name ||
                                            "Produk Terhapus"
                                        }}
                                    </div>
                                    <small
                                        class="text-muted font-monospace fs-11"
                                        >{{
                                            item.product?.barcode || "—"
                                        }}</small
                                    >
                                </td>
                                <td class="text-center text-secondary">
                                    {{ item.product?.unit || "pcs" }}
                                </td>
                                <td class="text-center fw-bold text-dark">
                                    {{ item.qty }}
                                </td>
                                <td class="text-end font-monospace">
                                    {{ fmt(item.cost_price) }}
                                </td>
                                <td
                                    class="text-end fw-bold text-dark pe-3 font-monospace"
                                >
                                    {{ fmt(item.subtotal) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Note and Grand Total Summary -->
                <div class="row align-items-start g-4">
                    <!-- Left: Note -->
                    <div class="col-md-7">
                        <CCard class="bg-light border-0 shadow-none">
                            <CCardBody class="p-3">
                                <strong class="text-dark d-block mb-1"
                                    >Catatan Penerimaan:</strong
                                >
                                <p
                                    class="text-secondary mb-0 fs-13"
                                    style="white-space: pre-line"
                                >
                                    {{
                                        receipt.note ||
                                        "Tidak ada catatan tambahan untuk penerimaan barang ini."
                                    }}
                                </p>
                            </CCardBody>
                        </CCard>
                    </div>

                    <!-- Right: Grand Total -->
                    <div class="col-md-5">
                        <div class="p-3 border rounded text-md-end bg-white">
                            <small
                                class="text-muted d-block mb-1 text-uppercase fw-semibold fs-11"
                                >TOTAL PENGELUARAN (HARGA MODAL)</small
                            >
                            <h2 class="text-danger fw-bold font-monospace mb-0">
                                {{ fmt(receipt.total_cost) }}
                            </h2>
                        </div>
                    </div>
                </div>

                <!-- Signature Area for printed version -->
                <div
                    class="row mt-5 pt-4 d-none d-print-flex justify-content-between"
                >
                    <div class="col-4 text-center">
                        <p class="text-muted mb-5 pb-3">Petugas Penerima,</p>
                        <hr class="w-75 mx-auto" />
                        <strong class="text-dark">{{
                            receipt.user?.name
                        }}</strong>
                    </div>
                    <div class="col-4 text-center">
                        <p class="text-muted mb-5 pb-3">
                            Kurir Pengirim / Supplier,
                        </p>
                        <hr class="w-75 mx-auto" />
                        <strong class="text-dark">{{
                            receipt.supplier?.name
                        }}</strong>
                    </div>
                </div>
            </CCardBody>
        </CCard>
    </div>
</template>

<style>
@media print {
    /* Hide layout sidebar, header, buttons and background wrappers */
    .d-print-none,
    .sidebar,
    .header,
    .footer,
    nav,
    .btn,
    button {
        display: none !important;
    }

    /* Make print sheet fill the paper fully */
    body,
    .wrapper,
    .body,
    main,
    .container-fluid {
        background: #ffffff !important;
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
    }

    #print-area {
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
        margin: 0 !important;
        width: 100% !important;
    }
}
</style>
