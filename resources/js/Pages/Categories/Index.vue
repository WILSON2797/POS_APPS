<script setup>
import { ref, h } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import DefaultLayout from '@/Layouts/DefaultLayout.vue'
import BaseDataTable from '@/Components/Base/BaseDataTable.vue'
import BaseModal from '@/Components/Base/BaseModal.vue'
import BaseInput from '@/Components/Base/BaseInput.vue'
import BaseTextarea from '@/Components/Base/BaseTextarea.vue'
import BaseButton from '@/Components/Base/BaseButton.vue'
import { useSwal } from '@/composables/useSwal'

defineOptions({ layout: DefaultLayout })

const props = defineProps({ categories: Array })
const { showSuccess, showError, showConfirm } = useSwal()

const columns = [
  {
    id: 'no', header: '#', size: 50, enableSorting: false, enableColumnFilter: false,
    cell: ({ row }) => row.index + 1,
  },
  {
    accessorKey: 'name',
    header: 'Nama Kategori',
    enableColumnFilter: true,
  },
  {
    accessorKey: 'description',
    header: 'Deskripsi',
    enableColumnFilter: true,
    cell: ({ getValue }) => getValue() ?? '-',
  },
  {
    accessorKey: 'products_count',
    header: 'Produk',
    size: 90,
    enableColumnFilter: false,
    cell: ({ getValue }) => h('span', { class: 'badge bg-info text-dark' }, getValue()),
  },
  {
    id: 'actions',
    header: 'Aksi',
    size: 80,
    enableSorting: false,
    enableColumnFilter: false,
    cell: ({ row }) => h('div', { class: 'd-flex gap-1 justify-content-center align-items-center' }, [
      h('button', {
        class: 'btn btn-warning btn-sm d-inline-flex align-items-center justify-content-center p-0',
        style: 'width: 30px; height: 30px; border-radius: 6px;',
        title: 'Edit',
        onClick: () => openEdit(row.original)
      }, [
        h('svg', {
          xmlns: 'http://www.w3.org/2000/svg',
          width: '14',
          height: '14',
          viewBox: '0 0 24 24',
          fill: 'none',
          stroke: 'currentColor',
          'stroke-width': '2.5',
          'stroke-linecap': 'round',
          'stroke-linejoin': 'round'
        }, [
          h('path', { d: 'M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7' }),
          h('path', { d: 'M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z' })
        ])
      ]),
      h('button', {
        class: 'btn btn-danger btn-sm d-inline-flex align-items-center justify-content-center p-0',
        style: 'width: 30px; height: 30px; border-radius: 6px;',
        title: 'Hapus',
        onClick: () => openDelete(row.original)
      }, [
        h('svg', {
          xmlns: 'http://www.w3.org/2000/svg',
          width: '14',
          height: '14',
          viewBox: '0 0 24 24',
          fill: 'none',
          stroke: 'currentColor',
          'stroke-width': '2.5',
          'stroke-linecap': 'round',
          'stroke-linejoin': 'round'
        }, [
          h('polyline', { points: '3 6 5 6 21 6' }),
          h('path', { d: 'M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2' }),
          h('line', { x1: '10', y1: '11', x2: '10', y2: '17' }),
          h('line', { x1: '14', y1: '11', x2: '14', y2: '17' })
        ])
      ])
    ]),
  },
]

// Form
const showModal  = ref(false)
const editTarget = ref(null)
const form = useForm({ name: '', description: '' })

function openAdd()  { editTarget.value = null; form.reset(); showModal.value = true }
function openEdit(cat) { editTarget.value = cat; form.name = cat.name; form.description = cat.description ?? ''; showModal.value = true }
function closeModal() { showModal.value = false; form.reset() }

function submitForm() {
  const opts = {
    onSuccess: () => { closeModal(); showSuccess('Kategori berhasil disimpan.') },
    onError:   () => showError('Periksa kembali form Anda.'),
  }
  if (editTarget.value) form.put(`/categories/${editTarget.value.id}`, opts)
  else form.post('/categories', opts)
}

async function openDelete(cat) {
  const r = await showConfirm({ text: `Hapus kategori "${cat.name}"?` })
  if (!r.isConfirmed) return
  router.delete(`/categories/${cat.id}`, {
    onSuccess: () => showSuccess('Kategori berhasil dihapus.'),
    onError:   () => showError('Gagal menghapus.'),
  })
}

async function handleBulkDelete(rows) {
  const r = await showConfirm({ text: `Hapus ${rows.length} kategori yang dipilih?` })
  if (!r.isConfirmed) return
  showSuccess(`${rows.length} kategori dihapus.`) // TODO: implement bulk endpoint
}
</script>

<template>
  <div>
    <CCard>
      <CCardHeader class="d-flex justify-content-between align-items-center">
        <strong>Kategori Produk</strong>
        <BaseButton @click="openAdd">
          + Tambah Kategori
        </BaseButton>
      </CCardHeader>
      <CCardBody>
        <BaseDataTable
          :columns="columns"
          :data="categories"
          search-placeholder="Cari kategori..."
          empty-text="Belum ada kategori."
          @bulk-delete="handleBulkDelete"
        />
      </CCardBody>
    </CCard>

    <BaseModal
      :show="showModal"
      :title="editTarget ? 'Edit Kategori' : 'Tambah Kategori'"
      :loading="form.processing"
      @close="closeModal"
      @confirm="submitForm"
    >
      <BaseInput v-model="form.name" label="Nama Kategori" :error="form.errors.name" required />
      <BaseTextarea v-model="form.description" label="Deskripsi" :error="form.errors.description" />
    </BaseModal>
  </div>
</template>
