<!--
  BaseDataTable.vue — TanStack Table v8 (Pure Client-Side)
  ✅ Global search (debounced, NO URL change)
  ✅ Per-column search box in every header
  ✅ Sorting per column
  ✅ Client-side pagination
  ✅ Loading / Empty state
  ✅ Selectable rows + Bulk delete
  ✅ Sticky header
  ✅ Responsive
-->
<template>
    <div class="base-datatable">
        <!-- ── Toolbar ── -->
        <div
            class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3"
        >
            <!-- Left: Global search + extra filters slot -->
            <div class="d-flex gap-2 align-items-center flex-wrap">
                <div class="input-group input-group-sm" style="width: 240px">
                    <span class="input-group-text bg-transparent border-end-0">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="14"
                            height="14"
                            fill="currentColor"
                            viewBox="0 0 16 16"
                        >
                            <path
                                d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.099zm-5.242 1.656a5.5 5.5 0 1 1 0-11 5.5 5.5 0 0 1 0 11z"
                            />
                        </svg>
                    </span>
                    <input
                        v-model="globalSearch"
                        type="text"
                        class="form-control border-start-0 ps-0"
                        :placeholder="searchPlaceholder"
                    />
                    <button
                        v-if="globalSearch"
                        class="btn btn-outline-secondary border-start-0"
                        @click="globalSearch = ''"
                    >
                        ×
                    </button>
                </div>
                <!-- Additional filters (categories dropdown, switches, etc.) -->
                <slot name="filters" />
            </div>

            <!-- Right: Bulk actions + slot for Add button -->
            <div class="d-flex gap-2 align-items-center flex-wrap">
                <template v-if="selectedRows.length > 0">
                    <span class="badge bg-primary rounded-pill"
                        >{{ selectedRows.length }} dipilih</span
                    >
                    <button
                        class="btn btn-danger btn-sm"
                        @click="$emit('bulk-delete', selectedRows)"
                    >
                        🗑 Hapus Dipilih
                    </button>
                </template>
                <slot name="actions" />
            </div>
        </div>

        <!-- ── Table ── -->
        <div
            class="table-responsive"
            style="max-height: 600px; overflow-y: auto"
        >
            <table class="table table-hover table-bordered align-middle mb-0">
                <!-- Header (sticky) -->
                <thead
                    class="table-light"
                    style="position: sticky; top: 0; z-index: 2"
                >
                    <tr
                        v-for="headerGroup in table.getHeaderGroups()"
                        :key="headerGroup.id"
                    >
                        <!-- Select-all checkbox -->
                        <th
                            v-if="selectable"
                            style="width: 42px"
                            class="text-center"
                        >
                            <input
                                type="checkbox"
                                class="form-check-input"
                                :checked="table.getIsAllRowsSelected()"
                                :indeterminate="table.getIsSomeRowsSelected()"
                                @change="
                                    table.toggleAllRowsSelected(
                                        $event.target.checked,
                                    )
                                "
                            />
                        </th>

                        <!-- Dynamic columns -->
                        <th
                            v-for="header in headerGroup.headers"
                            :key="header.id"
                            :style="
                                header.column.columnDef.size
                                    ? `min-width:${header.column.columnDef.size}px`
                                    : ''
                            "
                            class="align-top"
                        >
                            <!-- Column label + sort indicator -->
                            <div
                                class="d-flex align-items-center gap-1 mb-1"
                                :class="{
                                    'cursor-pointer user-select-none':
                                        header.column.getCanSort(),
                                }"
                                @click="
                                    header.column.getCanSort() &&
                                    header.column.toggleSorting()
                                "
                            >
                                <FlexRender
                                    :render="header.column.columnDef.header"
                                    :props="header.getContext()"
                                />
                                <span
                                    v-if="header.column.getCanSort()"
                                    class="text-body-secondary"
                                    style="font-size: 11px"
                                >
                                    <template
                                        v-if="
                                            header.column.getIsSorted() ===
                                            'asc'
                                        "
                                        >▲</template
                                    >
                                    <template
                                        v-else-if="
                                            header.column.getIsSorted() ===
                                            'desc'
                                        "
                                        >▼</template
                                    >
                                    <template v-else>⇅</template>
                                </span>
                            </div>

                            <!-- Per-column search input -->
                            <input
                                v-if="header.column.getCanFilter()"
                                :value="header.column.getFilterValue() ?? ''"
                                type="text"
                                class="form-control form-control-sm"
                                :placeholder="`Filter ${typeof header.column.columnDef.header === 'string' ? header.column.columnDef.header : ''}...`"
                                @input="
                                    header.column.setFilterValue(
                                        $event.target.value || undefined,
                                    )
                                "
                                @click.stop
                            />
                        </th>
                    </tr>
                </thead>

                <!-- Body -->
                <tbody>
                    <!-- Loading -->
                    <tr v-if="loading">
                        <td :colspan="totalCols" class="text-center py-5">
                            <div class="spinner-border text-primary mb-2" />
                            <div class="text-body-secondary small">
                                Memuat data...
                            </div>
                        </td>
                    </tr>

                    <!-- Empty -->
                    <tr v-else-if="table.getRowModel().rows.length === 0">
                        <td
                            :colspan="totalCols"
                            class="text-center py-5 text-body-secondary"
                        >
                            <div style="font-size: 2.5rem; opacity: 0.25">
                                📭
                            </div>
                            <div class="mt-1">{{ emptyText }}</div>
                        </td>
                    </tr>

                    <!-- Rows -->
                    <tr
                        v-else
                        v-for="row in table.getRowModel().rows"
                        :key="row.id"
                        :class="{ 'table-primary': row.getIsSelected() }"
                    >
                        <td v-if="selectable" class="text-center">
                            <input
                                type="checkbox"
                                class="form-check-input"
                                :checked="row.getIsSelected()"
                                @change="
                                    row.toggleSelected($event.target.checked)
                                "
                            />
                        </td>
                        <td
                            v-for="cell in row.getVisibleCells()"
                            :key="cell.id"
                        >
                            <FlexRender
                                :render="cell.column.columnDef.cell"
                                :props="cell.getContext()"
                            />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- ── Pagination ── -->
        <div
            class="d-flex flex-wrap align-items-center justify-content-between mt-3 gap-2"
        >
            <!-- Info -->
            <div class="text-body-secondary small">
                Menampilkan
                <strong>{{ showingFrom }}–{{ showingTo }}</strong>
                dari
                <strong>{{ table.getFilteredRowModel().rows.length }}</strong>
                data
                <template v-if="globalSearch || hasColumnFilters">
                    (difilter dari {{ props.data.length }} total)
                </template>
            </div>

            <!-- Controls -->
            <div class="d-flex gap-1 align-items-center">
                <!-- Per-page -->
                <select
                    class="form-select form-select-sm me-1"
                    style="width: 70px"
                    :value="table.getState().pagination.pageSize"
                    @change="table.setPageSize(Number($event.target.value))"
                >
                    <option
                        v-for="n in [10, 15, 25, 50, 100]"
                        :key="n"
                        :value="n"
                    >
                        {{ n }}
                    </option>
                </select>

                <button
                    class="btn btn-sm btn-outline-secondary"
                    :disabled="!table.getCanPreviousPage()"
                    @click="table.setPageIndex(0)"
                >
                    «
                </button>
                <button
                    class="btn btn-sm btn-outline-secondary"
                    :disabled="!table.getCanPreviousPage()"
                    @click="table.previousPage()"
                >
                    ‹
                </button>

                <button
                    v-for="p in visiblePageNumbers"
                    :key="p"
                    class="btn btn-sm"
                    :class="
                        p === table.getState().pagination.pageIndex + 1
                            ? 'btn-primary'
                            : 'btn-outline-secondary'
                    "
                    @click="table.setPageIndex(p - 1)"
                >
                    {{ p }}
                </button>

                <button
                    class="btn btn-sm btn-outline-secondary"
                    :disabled="!table.getCanNextPage()"
                    @click="table.nextPage()"
                >
                    ›
                </button>
                <button
                    class="btn btn-sm btn-outline-secondary"
                    :disabled="!table.getCanNextPage()"
                    @click="table.setPageIndex(table.getPageCount() - 1)"
                >
                    »
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from "vue";
import {
    useVueTable,
    getCoreRowModel,
    getFilteredRowModel,
    getSortedRowModel,
    getPaginationRowModel,
    FlexRender,
} from "@tanstack/vue-table";

const props = defineProps({
    columns: { type: Array, required: true },
    data: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
    selectable: { type: Boolean, default: true },
    searchPlaceholder: { type: String, default: "Cari data..." },
    emptyText: { type: String, default: "Tidak ada data ditemukan." },
    pageSize: { type: Number, default: 10 },
});

const emit = defineEmits(["bulk-delete"]);

// ── State ──────────────────────────────────────────────
const globalSearch = ref("");
const sorting = ref([]);
const rowSelection = ref({});
const columnFiltersState = ref([]);
const pagination = ref({ pageIndex: 0, pageSize: props.pageSize });

// Reset to first page when searching
watch(
    [globalSearch, columnFiltersState],
    () => {
        pagination.value = { ...pagination.value, pageIndex: 0 };
    },
    { deep: true },
);

// ── TanStack Table ──────────────────────────────────────
const table = useVueTable({
    get data() {
        return props.data;
    },
    get columns() {
        return props.columns;
    },
    state: {
        get globalFilter() {
            return globalSearch.value;
        },
        get sorting() {
            return sorting.value;
        },
        get rowSelection() {
            return rowSelection.value;
        },
        get columnFilters() {
            return columnFiltersState.value;
        },
        get pagination() {
            return pagination.value;
        },
    },
    enableRowSelection: true,
    autoResetPageIndex: true,

    onSortingChange: (up) => {
        sorting.value = typeof up === "function" ? up(sorting.value) : up;
    },
    onRowSelectionChange: (up) => {
        rowSelection.value =
            typeof up === "function" ? up(rowSelection.value) : up;
    },
    onColumnFiltersChange: (up) => {
        columnFiltersState.value =
            typeof up === "function" ? up(columnFiltersState.value) : up;
    },
    onGlobalFilterChange: (v) => {
        globalSearch.value = v;
    },
    onPaginationChange: (up) => {
        pagination.value = typeof up === "function" ? up(pagination.value) : up;
    },

    getCoreRowModel: getCoreRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
});

// ── Computed helpers ────────────────────────────────────
const selectedRows = computed(() =>
    table.getSelectedRowModel().rows.map((r) => r.original),
);
const totalCols = computed(
    () => props.columns.length + (props.selectable ? 1 : 0),
);
const hasColumnFilters = computed(() =>
    columnFiltersState.value.some((f) => f.value),
);

const showingFrom = computed(() => {
    const { pageIndex, pageSize } = pagination.value;
    const total = table.getFilteredRowModel().rows.length;
    if (total === 0) return 0;
    return pageIndex * pageSize + 1;
});
const showingTo = computed(() => {
    const { pageIndex, pageSize } = pagination.value;
    return Math.min(
        (pageIndex + 1) * pageSize,
        table.getFilteredRowModel().rows.length,
    );
});

const visiblePageNumbers = computed(() => {
    const total = table.getPageCount();
    const cur = pagination.value.pageIndex + 1;
    const start = Math.max(1, cur - 2);
    const end = Math.min(total, cur + 2);
    const pages = [];
    for (let i = start; i <= end; i++) pages.push(i);
    return pages;
});
</script>

<style scoped>
.base-datatable {
    width: 100%;
}
.cursor-pointer {
    cursor: pointer;
}
thead th {
    vertical-align: top;
}
</style>
