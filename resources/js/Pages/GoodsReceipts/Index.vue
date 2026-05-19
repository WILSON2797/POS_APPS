<script setup>
import { h } from "vue";
import { router } from "@inertiajs/vue3";
import DefaultLayout from "@/Layouts/DefaultLayout.vue";
import BaseDataTable from "@/Components/Base/BaseDataTable.vue";
import BaseButton from "@/Components/Base/BaseButton.vue";

defineOptions({ layout: DefaultLayout });

const props = defineProps({
    receipts: Object, // Paginated object
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
        month: "short",
        year: "numeric",
    });
};

const columns = [
    {
        id: "no",
        header: "#",
        size: 50,
        enableSorting: false,
        enableColumnFilter: false,
        cell: ({ row }) => row.index + 1,
    },
    {
        accessorKey: "receipt_no",
        header: "No. Penerimaan",
        enableColumnFilter: true,
        cell: ({ row: { original: r } }) =>
            h(
                "span",
                { class: "fw-bold font-monospace text-primary" },
                r.receipt_no
            ),
    },
    {
        accessorKey: "supplier_name",
        header: "Supplier",
        enableColumnFilter: true,
        accessorFn: (row) => row.supplier?.name ?? "",
        cell: ({ getValue }) => getValue() || "-",
    },
    {
        accessorKey: "receipt_date",
        header: "Tanggal Terima",
        enableSorting: true,
        enableColumnFilter: false,
        cell: ({ getValue }) => fmtDate(getValue()),
    },
    {
        accessorKey: "reference_no",
        header: "No. Referensi / Surat Jalan",
        enableColumnFilter: true,
        cell: ({ getValue }) =>
            h("span", { class: "font-monospace" }, getValue() || "—"),
    },
    {
        accessorKey: "total_cost",
        header: "Total Pengeluaran",
        enableSorting: true,
        enableColumnFilter: false,
        cell: ({ getValue }) =>
            h("span", { class: "fw-semibold text-danger" }, fmt(getValue())),
    },
    {
        accessorKey: "user_name",
        header: "Petugas",
        enableColumnFilter: true,
        accessorFn: (row) => row.user?.name ?? "",
        cell: ({ getValue }) => getValue() || "-",
    },
    {
        id: "actions",
        header: "Aksi",
        size: 80,
        enableSorting: false,
        enableColumnFilter: false,
        cell: ({ row }) =>
            h("div", { class: "d-flex justify-content-center" }, [
                h(
                    "button",
                    {
                        class: "btn btn-outline-primary btn-sm d-inline-flex align-items-center justify-content-center p-0",
                        style: "width: 32px; height: 32px; border-radius: 6px;",
                        title: "Detail Penerimaan",
                        onClick: () =>
                            router.visit(`/goods-receipts/${row.original.id}`),
                    },
                    [
                        h(
                            "svg",
                            {
                                xmlns: "http://www.w3.org/2000/svg",
                                width: "16",
                                height: "16",
                                viewBox: "0 0 24 24",
                                fill: "none",
                                stroke: "currentColor",
                                "stroke-width": "2.5",
                                "stroke-linecap": "round",
                                "stroke-linejoin": "round",
                            },
                            [
                                h("path", {
                                    d: "M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z",
                                }),
                                h("circle", { cx: "12", cy: "12", r: "3" }),
                            ]
                        ),
                    ]
                ),
            ]),
    },
];
</script>

<template>
    <div>
        <CCard class="shadow-sm border-0 mb-4">
            <CCardHeader
                class="bg-white border-0 py-3 d-flex justify-content-between align-items-center"
            >
                <div>
                    <h5 class="mb-0 fw-bold text-dark">Riwayat Penerimaan Barang</h5>
                    <small class="text-muted"
                        >Kelola dan audit mutasi masuk dari pembelian supplier</small
                    >
                </div>
                <BaseButton
                    class="btn-primary d-inline-flex align-items-center gap-2 px-4 shadow-sm"
                    style="border-radius: 8px;"
                    @click="router.visit('/goods-receipts/create')"
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
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    Penerimaan Baru
                </BaseButton>
            </CCardHeader>
            <CCardBody class="pt-0">
                <BaseDataTable
                    :columns="columns"
                    :data="receipts.data"
                    search-placeholder="Cari nomor penerimaan, supplier..."
                    empty-text="Belum ada transaksi penerimaan barang yang tercatat."
                />

                <!-- Pagination Footer -->
                <div
                    v-if="receipts.links && receipts.links.length > 3"
                    class="d-flex justify-content-between align-items-center mt-3"
                >
                    <small class="text-muted">
                        Menampilkan {{ receipts.from || 0 }} sampai
                        {{ receipts.to || 0 }} dari
                        {{ receipts.total }} penerimaan
                    </small>
                    <nav aria-label="Page navigation">
                        <ul class="pagination pagination-sm mb-0">
                            <li
                                v-for="(link, idx) in receipts.links"
                                :key="idx"
                                class="page-item"
                                :class="{
                                    active: link.active,
                                    disabled: !link.url,
                                }"
                            >
                                <button
                                    class="page-link"
                                    :disabled="!link.url"
                                    @click="router.visit(link.url)"
                                    v-html="link.label"
                                ></button>
                            </li>
                        </ul>
                    </nav>
                </div>
            </CCardBody>
        </CCard>
    </div>
</template>
