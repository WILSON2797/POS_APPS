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

const props = defineProps({ customers: Array })
const { showSuccess, showError, showConfirm } = useSwal()

const levelConfig = {
  regular:  { label: 'Regular',  cls: 'bg-secondary' },
  silver:   { label: 'Silver',   cls: 'bg-light text-dark border' },
  gold:     { label: 'Gold',     cls: 'bg-warning text-dark' },
  platinum: { label: 'Platinum', cls: 'bg-dark' },
}

const columns = [
  { id: 'no', header: '#', size: 50, enableSorting: false, enableColumnFilter: false, cell: ({ row }) => row.index + 1 },
  { accessorKey: 'name',   header: 'Nama',   enableColumnFilter: true },
  { accessorKey: 'phone',  header: 'Phone',  enableColumnFilter: true, cell: ({ getValue }) => getValue() ?? '-' },
  { accessorKey: 'email',  header: 'Email',  enableColumnFilter: true, cell: ({ getValue }) => getValue() ?? '-' },
  {
    accessorKey: 'points', header: 'Poin', size: 90, enableSorting: true, enableColumnFilter: false,
    cell: ({ getValue }) => h('span', { class: 'badge bg-primary' }, `${getValue()} poin`),
  },
  {
    accessorKey: 'level', header: 'Level', size: 100, enableColumnFilter: false,
    cell: ({ getValue }) => {
      const c = levelConfig[getValue()] ?? levelConfig.regular
      return h('span', { class: `badge ${c.cls}` }, c.label)
    },
  },
  {
    id: 'actions', header: 'Aksi', size: 110, enableSorting: false, enableColumnFilter: false,
    cell: ({ row }) => h('div', { class: 'd-flex gap-1 justify-content-end' }, [
      h('button', { class: 'btn btn-warning btn-sm', onClick: () => openEdit(row.original) }, '✏️ Edit'),
      h('button', { class: 'btn btn-danger btn-sm',  onClick: () => openDelete(row.original) }, '🗑'),
    ]),
  },
]

const showModal  = ref(false)
const editTarget = ref(null)
const form = useForm({ name: '', phone: '', email: '', address: '' })

function openAdd()  { editTarget.value = null; form.reset(); showModal.value = true }
function openEdit(c) { editTarget.value = c; form.name = c.name; form.phone = c.phone ?? ''; form.email = c.email ?? ''; form.address = c.address ?? ''; showModal.value = true }
function closeModal() { showModal.value = false; form.reset() }

function submitForm() {
  const opts = {
    onSuccess: () => { closeModal(); showSuccess('Customer berhasil disimpan.') },
    onError:   () => showError('Periksa kembali form.'),
  }
  if (editTarget.value) form.put(`/customers/${editTarget.value.id}`, opts)
  else form.post('/customers', opts)
}

async function openDelete(c) {
  const r = await showConfirm({ text: `Hapus customer "${c.name}"?` })
  if (!r.isConfirmed) return
  router.delete(`/customers/${c.id}`, {
    onSuccess: () => showSuccess('Customer dihapus.'),
    onError:   () => showError('Gagal.'),
  })
}

async function handleBulkDelete(rows) {
  const r = await showConfirm({ text: `Hapus ${rows.length} customer?` })
  if (!r.isConfirmed) return
  showSuccess(`${rows.length} customer dihapus.`)
}
</script>

<template>
  <div>
    <CCard>
      <CCardHeader class="d-flex justify-content-between align-items-center">
        <strong>Customer / Member</strong>
        <BaseButton @click="openAdd">+ Tambah Customer</BaseButton>
      </CCardHeader>
      <CCardBody>
        <BaseDataTable
          :columns="columns"
          :data="customers"
          search-placeholder="Cari nama, phone, email..."
          empty-text="Belum ada customer."
          @bulk-delete="handleBulkDelete"
        />
      </CCardBody>
    </CCard>

    <BaseModal
      :show="showModal"
      :title="editTarget ? 'Edit Customer' : 'Tambah Customer'"
      :loading="form.processing"
      @close="closeModal"
      @confirm="submitForm"
    >
      <div class="row">
        <div class="col-md-6">
          <BaseInput v-model="form.name"  label="Nama" :error="form.errors.name" required />
          <BaseInput v-model="form.phone" label="Phone" />
        </div>
        <div class="col-md-6">
          <BaseInput    v-model="form.email"   label="Email" type="email" />
          <BaseTextarea v-model="form.address" label="Alamat" />
        </div>
      </div>
    </BaseModal>
  </div>
</template>
