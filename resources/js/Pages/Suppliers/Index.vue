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

const props = defineProps({ suppliers: Array })
const { showSuccess, showError, showConfirm } = useSwal()

const columns = [
  {
    id: 'no', header: '#', size: 50, enableSorting: false, enableColumnFilter: false,
    cell: ({ row }) => row.index + 1,
  },
  {
    accessorKey: 'name',
    header: 'Nama Supplier',
    enableColumnFilter: true,
    cell: ({ row: { original: s } }) => h('div', [
      h('div', { class: 'fw-semibold' }, s.name),
      s.contact_person ? h('small', { class: 'text-body-secondary' }, s.contact_person) : null,
    ]),
  },
  {
    accessorKey: 'phone',
    header: 'Phone / WA',
    enableColumnFilter: true,
    cell: ({ row: { original: s } }) => h('div', [
      h('div', {}, s.phone ?? '-'),
      s.whatsapp ? h('small', { class: 'text-success' }, `WA: ${s.whatsapp}`) : null,
    ]),
  },
  {
    accessorKey: 'email',
    header: 'Email',
    enableColumnFilter: true,
    cell: ({ getValue }) => getValue() ?? '-',
  },
  {
    accessorKey: 'address',
    header: 'Alamat',
    enableColumnFilter: true,
    cell: ({ getValue }) =>
      h('span', {
        style: 'max-width:180px;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap',
        title: getValue() ?? '',
      }, getValue() ?? '-'),
  },
  {
    accessorKey: 'products_count',
    header: 'Produk',
    size: 80,
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

const showModal  = ref(false)
const editTarget = ref(null)
const form = useForm({ name: '', contact_person: '', phone: '', whatsapp: '', email: '', address: '' })

function openAdd()  { editTarget.value = null; form.reset(); showModal.value = true }
function openEdit(s) {
  editTarget.value = s
  form.name = s.name; form.contact_person = s.contact_person ?? ''
  form.phone = s.phone ?? ''; form.whatsapp = s.whatsapp ?? ''
  form.email = s.email ?? ''; form.address = s.address ?? ''
  showModal.value = true
}
function closeModal() { showModal.value = false; form.reset() }

function submitForm() {
  const opts = {
    onSuccess: () => { closeModal(); showSuccess('Supplier berhasil disimpan.') },
    onError:   () => showError('Periksa kembali form Anda.'),
  }
  if (editTarget.value) form.put(`/suppliers/${editTarget.value.id}`, opts)
  else form.post('/suppliers', opts)
}

async function openDelete(s) {
  const r = await showConfirm({ text: `Hapus supplier "${s.name}"?` })
  if (!r.isConfirmed) return
  router.delete(`/suppliers/${s.id}`, {
    onSuccess: () => showSuccess('Supplier berhasil dihapus.'),
    onError:   () => showError('Gagal menghapus.'),
  })
}

async function handleBulkDelete(rows) {
  const r = await showConfirm({ text: `Hapus ${rows.length} supplier?` })
  if (!r.isConfirmed) return
  showSuccess(`${rows.length} supplier dihapus.`)
}
</script>

<template>
  <div>
    <CCard>
      <CCardHeader class="d-flex justify-content-between align-items-center">
        <strong>Supplier</strong>
        <BaseButton @click="openAdd">+ Tambah Supplier</BaseButton>
      </CCardHeader>
      <CCardBody>
        <BaseDataTable
          :columns="columns"
          :data="suppliers"
          search-placeholder="Cari nama, phone, email, alamat..."
          empty-text="Belum ada supplier."
          @bulk-delete="handleBulkDelete"
        />
      </CCardBody>
    </CCard>

    <BaseModal
      :show="showModal"
      :title="editTarget ? 'Edit Supplier' : 'Tambah Supplier'"
      :loading="form.processing"
      @close="closeModal"
      @confirm="submitForm"
    >
      <div class="row">
        <div class="col-md-6">
          <BaseInput v-model="form.name"           label="Nama Supplier" :error="form.errors.name" required />
          <BaseInput v-model="form.contact_person" label="Contact Person" />
          <BaseInput v-model="form.phone"          label="Phone" />
        </div>
        <div class="col-md-6">
          <BaseInput    v-model="form.whatsapp" label="WhatsApp" />
          <BaseInput    v-model="form.email"    label="Email" type="email" />
          <BaseTextarea v-model="form.address"  label="Alamat" />
        </div>
      </div>
    </BaseModal>
  </div>
</template>
