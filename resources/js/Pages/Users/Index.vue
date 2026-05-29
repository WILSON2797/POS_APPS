<script setup>
import { ref, h } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import DefaultLayout from '@/Layouts/DefaultLayout.vue'
import BaseDataTable from '@/Components/Base/BaseDataTable.vue'
import BaseModal from '@/Components/Base/BaseModal.vue'
import BaseInput from '@/Components/Base/BaseInput.vue'

defineOptions({ layout: DefaultLayout })

const props = defineProps({
  users: Array
})

const page = usePage()
const currentAuthUserId = page.props.auth?.user?.id

// State for Modal User (Add/Edit)
const showModal = ref(false)
const isEditMode = ref(false)
const currentUserId = ref(null)

// Initialize useForm hook
const form = useForm({
  name: '',
  email: '',
  role: 'kasir',
  password: ''
})

const openAddModal = () => {
  isEditMode.value = false
  currentUserId.value = null
  form.reset()
  form.clearErrors()
  showModal.value = true
}

const openEditModal = (user) => {
  isEditMode.value = true
  currentUserId.value = user.id
  form.clearErrors()
  form.name = user.name
  form.email = user.email
  form.role = user.role
  form.password = '' // Blank unless changing
  showModal.value = true
}

const submitForm = () => {
  if (isEditMode.value) {
    form.put(`/users/${currentUserId.value}`, {
      onSuccess: () => {
        showModal.value = false
        window.showSuccess('Pengguna berhasil diperbarui!')
      },
      onError: () => {
        window.showError('Gagal memperbarui pengguna. Silakan periksa kembali input Anda.')
      }
    })
  } else {
    form.post('/users', {
      onSuccess: () => {
        showModal.value = false
        window.showSuccess('Pengguna baru berhasil ditambahkan!')
      },
      onError: () => {
        window.showError('Gagal menambahkan pengguna baru. Silakan periksa kembali input Anda.')
      }
    })
  }
}

const toggleUserStatus = async (user) => {
  const result = await window.showConfirm({
    title: 'Konfirmasi Perubahan Status',
    text: `Apakah Anda yakin ingin ${user.is_active ? 'menonaktifkan' : 'mengaktifkan'} pengguna ${user.name}?`,
    confirmText: 'Ya, Ubah!'
  })

  if (result && result.isConfirmed) {
    router.post(`/users/${user.id}/toggle-status`, {}, {
      onSuccess: () => {
        window.showSuccess(`Status pengguna berhasil diperbarui!`)
      },
      onError: (errors) => {
        if (errors.error) {
          window.showError(errors.error)
        } else {
          window.showError('Terjadi kesalahan saat memproses status pengguna.')
        }
        router.reload({ only: ['users'] })
      }
    })
  } else {
    router.reload({ only: ['users'] })
  }
}

// Columns definition for TanStack Table
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
    accessorKey: 'name',
    header: 'Nama Pengguna',
    enableSorting: true,
    enableColumnFilter: true,
    cell: ({ getValue }) => h('span', { class: 'fw-bold text-dark' }, getValue())
  },
  {
    accessorKey: 'email',
    header: 'Alamat Email',
    enableSorting: true,
    enableColumnFilter: true,
    cell: ({ getValue }) => h('span', { class: 'font-monospace text-secondary' }, getValue())
  },
  {
    accessorKey: 'role',
    header: 'Akses Role',
    size: 130,
    enableSorting: true,
    enableColumnFilter: true,
    cell: ({ getValue }) => {
      const role = getValue()
      let bgClass = 'bg-primary-subtle text-primary border-primary-subtle'
      let label = 'KASIR'
      
      if (role === 'admin') {
        bgClass = 'bg-danger-subtle text-danger border-danger-subtle'
        label = 'ADMINISTRATOR'
      } else if (role === 'owner') {
        bgClass = 'bg-warning-subtle text-warning border-warning-subtle'
        label = 'OWNER'
      }

      return h('span', { class: `badge ${bgClass} border px-2 py-1 rounded-pill text-uppercase` }, label)
    }
  },
  {
    accessorKey: 'is_active',
    header: 'Status Akun',
    size: 110,
    enableSorting: true,
    enableColumnFilter: true,
    cell: ({ row: { original: user } }) => {
      const isSelf = user.id === currentAuthUserId
      return h('div', { class: 'form-check form-switch d-flex justify-content-center' }, [
        h('input', {
          class: 'form-check-input cursor-pointer',
          type: 'checkbox',
          role: 'switch',
          checked: user.is_active,
          disabled: isSelf,
          onChange: () => toggleUserStatus(user)
        })
      ])
    }
  },
  {
    id: 'actions',
    header: 'Aksi',
    size: 100,
    enableSorting: false,
    enableColumnFilter: false,
    cell: ({ row: { original: user } }) => {
      return h('div', { class: 'd-flex gap-1.5' }, [
        h('button', {
          class: 'btn btn-xs btn-outline-primary px-2.5 py-1 fw-bold rounded-2',
          onClick: () => openEditModal(user)
        }, 'Edit')
      ])
    }
  }
]
</script>

<template>
  <div class="users-page container-fluid px-4 py-4">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
      <div>
        <h3 class="m-0 fw-bold text-primary d-flex align-items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
            <circle cx="9" cy="7" r="4"></circle>
            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
          </svg>
          Manajemen User Kasir & Admin
        </h3>
        <p class="text-body-secondary m-0 mt-1">Kelola data login pengguna, berikan akses peran (role), dan aktifkan/nonaktifkan akun karyawan.</p>
      </div>
      <div>
        <button @click="openAddModal" class="btn btn-primary btn-sm d-flex align-items-center gap-2 px-3 py-2 fw-semibold rounded-3 shadow-sm">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
          Tambah User Baru
        </button>
      </div>
    </div>

    <!-- Users Table Card -->
    <div class="card border-0 shadow-sm rounded-4" style="overflow: hidden;">
      <div class="card-body p-3">
        <BaseDataTable
          :columns="columns"
          :data="users"
          :selectable="false"
          search-placeholder="Cari user berdasarkan nama, email..."
          empty-text="Belum ada data user ditemukan."
        />
      </div>
    </div>

    <!-- Reusable Modal (Add / Edit User) -->
    <BaseModal
      :show="showModal"
      :title="isEditMode ? 'Edit Informasi User' : 'Tambah User Baru'"
      confirm-text="Simpan Data"
      :loading="form.processing"
      @close="showModal = false"
      @confirm="submitForm"
    >
      <div class="row g-3">
        <div class="col-md-6">
          <BaseInput
            v-model="form.name"
            label="Nama Lengkap"
            placeholder="Masukkan nama lengkap user"
            required
            :error="form.errors.name"
          />
        </div>
        <div class="col-md-6">
          <BaseInput
            v-model="form.email"
            label="Alamat Email"
            type="email"
            placeholder="Masukkan email login"
            required
            :error="form.errors.email"
          />
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold text-secondary small mb-2">Akses Peran (Role) <span class="text-danger">*</span></label>
          <select v-model="form.role" class="form-select form-select-sm rounded-3" style="height: 38px;">
            <option value="kasir">Kasir / Staff</option>
            <option value="admin">Administrator</option>
            <option value="owner">Owner / Pemilik Toko</option>
          </select>
          <div v-if="form.errors.role" class="text-danger small mt-1">{{ form.errors.role }}</div>
        </div>
        <div class="col-md-6">
          <BaseInput
            v-model="form.password"
            label="Kata Sandi"
            type="password"
            :placeholder="isEditMode ? 'Kosongkan jika tidak diubah' : 'Masukkan password baru'"
            :required="!isEditMode"
            :error="form.errors.password"
          />
        </div>
      </div>
    </BaseModal>
  </div>
</template>

<style scoped>
:deep(.badge) {
  font-weight: 600;
  letter-spacing: 0.2px;
}
.btn-xs {
  padding: 0.25rem 0.5rem;
  font-size: 11.5px;
}
:deep(.form-label) {
  font-weight: 600;
  font-size: 13.5px;
  color: var(--cui-secondary-color, #4f5d73);
}
</style>
