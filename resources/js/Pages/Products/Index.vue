<script setup>
import { ref, computed, h, watch, nextTick } from "vue";
import { useForm, router } from "@inertiajs/vue3";
import DefaultLayout from "@/Layouts/DefaultLayout.vue";
import BaseDataTable from "@/Components/Base/BaseDataTable.vue";
import BaseModal from "@/Components/Base/BaseModal.vue";
import BaseInput from "@/Components/Base/BaseInput.vue";
import BaseTextarea from "@/Components/Base/BaseTextarea.vue";
import BaseSelect from "@/Components/Base/BaseSelect.vue";
import BaseButton from "@/Components/Base/BaseButton.vue";
import { useSwal } from "@/composables/useSwal";

defineOptions({ layout: DefaultLayout });

const props = defineProps({
    products: Array,
    categories: Array,
});
const { showSuccess, showError, showConfirm } = useSwal();

const fmt = (v) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        maximumFractionDigits: 0,
    }).format(v);

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
        accessorKey: "name",
        header: "Produk",
        enableColumnFilter: true,
        cell: ({ row: { original: p } }) =>
            h("div", { class: "d-flex align-items-center gap-2" }, [
                p.image
                    ? h("img", {
                          src: `/storage/${p.image}`,
                          style: "width:36px;height:36px;object-fit:cover;border-radius:6px;flex-shrink:0",
                      })
                    : h(
                          "div",
                          {
                              class: "bg-light d-flex align-items-center justify-content-center rounded",
                              style: "width:36px;height:36px;flex-shrink:0;font-size:18px",
                          },
                          "📦",
                      ),
                h("div", [
                    h("div", { class: "fw-semibold" }, p.name),
                    h(
                        "small",
                        { class: "text-body-secondary font-monospace" },
                        p.barcode ?? "—",
                    ),
                ]),
            ]),
    },
    {
        accessorKey: "category_name",
        header: "Kategori",
        enableColumnFilter: true,
        // Flatten for filtering
        accessorFn: (row) => row.category?.name ?? "",
        cell: ({ getValue }) => getValue() || "-",
    },
    {
        accessorKey: "selling_price",
        header: "Harga Jual",
        enableSorting: true,
        enableColumnFilter: false,
        cell: ({ getValue }) =>
            h("span", { class: "fw-semibold text-success" }, fmt(getValue())),
    },
    {
        accessorKey: "stock",
        header: "Stok",
        size: 100,
        enableSorting: true,
        enableColumnFilter: false,
        cell: ({ row: { original: p } }) =>
            h(
                "span",
                {
                    class: `badge bg-${p.stock <= p.min_stock ? "danger" : "success"}`,
                },
                `${p.stock} ${p.unit}`,
            ),
    },
    {
        accessorKey: "is_active",
        header: "Status",
        size: 90,
        enableColumnFilter: false,
        cell: ({ getValue }) =>
            h(
                "span",
                { class: `badge bg-${getValue() ? "success" : "secondary"}` },
                getValue() ? "Aktif" : "Nonaktif",
            ),
    },
    {
        id: "actions",
        header: "Aksi",
        size: 80,
        enableSorting: false,
        enableColumnFilter: false,
        cell: ({ row }) =>
            h(
                "div",
                {
                    class: "d-flex gap-1 justify-content-center align-items-center",
                },
                [
                    h(
                        "button",
                        {
                            class: "btn btn-warning btn-sm d-inline-flex align-items-center justify-content-center p-0",
                            style: "width: 30px; height: 30px; border-radius: 6px;",
                            title: "Edit",
                            onClick: () => openEdit(row.original),
                        },
                        [
                            h(
                                "svg",
                                {
                                    xmlns: "http://www.w3.org/2000/svg",
                                    width: "14",
                                    height: "14",
                                    viewBox: "0 0 24 24",
                                    fill: "none",
                                    stroke: "currentColor",
                                    "stroke-width": "2.5",
                                    "stroke-linecap": "round",
                                    "stroke-linejoin": "round",
                                },
                                [
                                    h("path", {
                                        d: "M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7",
                                    }),
                                    h("path", {
                                        d: "M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z",
                                    }),
                                ],
                            ),
                        ],
                    ),
                    h(
                        "button",
                        {
                            class: "btn btn-danger btn-sm d-inline-flex align-items-center justify-content-center p-0",
                            style: "width: 30px; height: 30px; border-radius: 6px;",
                            title: "Hapus",
                            onClick: () => openDelete(row.original),
                        },
                        [
                            h(
                                "svg",
                                {
                                    xmlns: "http://www.w3.org/2000/svg",
                                    width: "14",
                                    height: "14",
                                    viewBox: "0 0 24 24",
                                    fill: "none",
                                    stroke: "currentColor",
                                    "stroke-width": "2.5",
                                    "stroke-linecap": "round",
                                    "stroke-linejoin": "round",
                                },
                                [
                                    h("polyline", { points: "3 6 5 6 21 6" }),
                                    h("path", {
                                        d: "M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2",
                                    }),
                                    h("line", {
                                        x1: "10",
                                        y1: "11",
                                        x2: "10",
                                        y2: "17",
                                    }),
                                    h("line", {
                                        x1: "14",
                                        y1: "11",
                                        x2: "14",
                                        y2: "17",
                                    }),
                                ],
                            ),
                        ],
                    ),
                ],
            ),
    },
];

// Form
const showModal = ref(false);
const editTarget = ref(null);
const imagePreview = ref(null);
const form = useForm({
    barcode: "",
    name: "",
    category_id: null,
    cost_price: 0,
    selling_price: 0,
    stock: 0,
    min_stock: 5,
    unit: "pcs",
    description: "",
    image: null,
    is_active: true,
});

const margin = computed(() => {
    if (!form.cost_price || +form.cost_price === 0) return "0.0";
    return (
        ((+form.selling_price - +form.cost_price) / +form.cost_price) *
        100
    ).toFixed(1);
});
const marginColor = computed(() =>
    +margin.value >= 20 ? "success" : +margin.value >= 5 ? "warning" : "danger",
);

function openAdd() {
    editTarget.value = null;
    form.reset();
    imagePreview.value = null;
    showModal.value = true;
}
function openEdit(p) {
    editTarget.value = p;
    Object.assign(form, {
        barcode: p.barcode ?? "",
        name: p.name,
        category_id: p.category_id,
        cost_price: p.cost_price,
        selling_price: p.selling_price,
        stock: p.stock,
        min_stock: p.min_stock,
        unit: p.unit,
        description: p.description ?? "",
        is_active: p.is_active,
        image: null,
    });
    imagePreview.value = p.image ? `/storage/${p.image}` : null;
    showModal.value = true;
}
function closeModal() {
    showModal.value = false;
    imagePreview.value = null;
    form.reset();
}

watch(showModal, (newVal) => {
    if (newVal) {
        nextTick(() => {
            // Otomatis fokuskan kursor ke input Barcode / SKU saat modal terbuka
            setTimeout(() => {
                document.getElementById("barcode-sku-input")?.focus();
            }, 250); // small timeout to ensure modal rendering transition has completed
        });
    }
});

function onImageChange(e) {
    const file = e.target.files[0];
    if (!file) return;
    form.image = file;
    imagePreview.value = URL.createObjectURL(file);
}

function submitForm() {
    const opts = {
        forceFormData: true,
        onSuccess: () => {
            closeModal();
            showSuccess("Produk berhasil disimpan.");
        },
        onError: () => showError("Periksa kembali form Anda."),
    };
    if (editTarget.value)
        form.transform((d) => ({ ...d, _method: "PUT" })).post(
            `/products/${editTarget.value.id}`,
            opts,
        );
    else form.post("/products", opts);
}

async function openDelete(p) {
    const r = await showConfirm({ text: `Hapus produk "${p.name}"?` });
    if (!r.isConfirmed) return;
    router.delete(`/products/${p.id}`, {
        onSuccess: () => showSuccess("Produk berhasil dihapus."),
        onError: () => showError("Gagal menghapus."),
    });
}

async function handleBulkDelete(rows) {
    const r = await showConfirm({
        text: `Hapus ${rows.length} produk yang dipilih?`,
    });
    if (!r.isConfirmed) return;
    showSuccess(`${rows.length} produk dihapus.`);
}
</script>

<template>
    <div>
        <CCard>
            <CCardHeader
                class="d-flex justify-content-between align-items-center"
            >
                <strong>Master Produk</strong>
                <div class="d-flex gap-2">
                    <button
                        type="button"
                        class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center px-3"
                        style="
                            height: 38px;
                            font-weight: 600;
                            border-radius: 6px;
                        "
                        @click="router.visit('/products/stock-movements')"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="14"
                            height="14"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2.5"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            class="me-1"
                        >
                            <path
                                d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"
                            ></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <polyline points="10 9 9 9 8 9"></polyline>
                        </svg>
                        Kartu Stok
                    </button>
                    <BaseButton @click="openAdd">+ Tambah Produk</BaseButton>
                </div>
            </CCardHeader>
            <CCardBody>
                <BaseDataTable
                    :columns="columns"
                    :data="products"
                    search-placeholder="Cari nama, barcode, kategori..."
                    empty-text="Belum ada produk. Tambahkan sekarang."
                    @bulk-delete="handleBulkDelete"
                />
            </CCardBody>
        </CCard>

        <!-- Modal -->
        <BaseModal
            :show="showModal"
            :title="editTarget ? 'Edit Produk' : 'Tambah Produk'"
            :loading="form.processing"
            size="xl"
            @close="closeModal"
            @confirm="submitForm"
        >
            <div class="row g-3">
                <!-- Kolom kiri -->
                <div class="col-md-6">
                    <BaseInput
                        id="barcode-sku-input"
                        v-model="form.barcode"
                        label="Barcode / SKU"
                        placeholder="Scan atau ketik..."
                    />
                    <BaseInput
                        v-model="form.name"
                        label="Nama Produk"
                        :error="form.errors.name"
                        required
                    />
                    <BaseSelect
                        v-model="form.category_id"
                        label="Kategori"
                        :options="categories"
                        :error="form.errors.category_id"
                        required
                    />
                    <BaseInput
                        v-model="form.unit"
                        label="Satuan"
                        placeholder="pcs, kg, liter, dus..."
                    />
                </div>

                <!-- Kolom kanan -->
                <div class="col-md-6">
                    <BaseInput
                        v-model="form.cost_price"
                        label="Harga Modal (Rp)"
                        type="number"
                        min="0"
                    />
                    <BaseInput
                        v-model="form.selling_price"
                        label="Harga Jual (Rp)"
                        type="number"
                        min="0"
                    />

                    <!-- Margin indicator -->
                    <div
                        class="mb-3 p-2 rounded"
                        :class="`bg-${marginColor} bg-opacity-10 border border-${marginColor}`"
                    >
                        <small
                            class="fw-semibold"
                            :class="`text-${marginColor}`"
                        >
                            Margin: {{ margin }}%
                        </small>
                    </div>

                    <div class="mb-3">
                        <BaseInput
                            v-model="form.min_stock"
                            label="Min. Stok (Ambang Batas Peringatan)"
                            type="number"
                            min="0"
                        />
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Foto Produk</label>
                        <input
                            type="file"
                            class="form-control"
                            accept="image/*"
                            @change="onImageChange"
                        />
                        <img
                            v-if="imagePreview"
                            :src="imagePreview"
                            class="img-thumbnail mt-2"
                            style="max-height: 80px"
                        />
                    </div>

                    <div class="form-check form-switch">
                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            class="form-check-input"
                            id="chkActive"
                        />
                        <label class="form-check-label" for="chkActive"
                            >Produk Aktif</label
                        >
                    </div>
                </div>

                <div class="col-12">
                    <BaseTextarea
                        v-model="form.description"
                        label="Deskripsi"
                        :rows="2"
                    />
                </div>
            </div>
        </BaseModal>
    </div>
</template>
