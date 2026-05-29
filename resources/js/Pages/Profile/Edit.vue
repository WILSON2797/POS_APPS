<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import DefaultLayout from '@/Layouts/DefaultLayout.vue'
import BaseInput from '@/Components/Base/BaseInput.vue'

defineOptions({ layout: DefaultLayout })

const props = defineProps({
  user: Object
})

// Initialize form using Inertia's useForm hook
const form = useForm({
  name: props.user.name,
  email: props.user.email,
  current_password: '',
  new_password: '',
  new_password_confirmation: ''
})

const isSubmitting = ref(false)

// Handle form submission
const updateProfile = () => {
  isSubmitting.value = true
  form.put('/profile', {
    preserveScroll: true,
    onSuccess: () => {
      isSubmitting.value = false
      window.showSuccess('Profil Anda berhasil diperbarui!')
      // Clear password inputs
      form.current_password = ''
      form.new_password = ''
      form.new_password_confirmation = ''
    },
    onError: (errors) => {
      isSubmitting.value = false
      if (errors.current_password) {
        window.showError(errors.current_password)
      } else {
        window.showError('Gagal memperbarui profil. Silakan periksa kembali input Anda.')
      }
    }
  })
}

// Compute initials for the avatar representation
const initials = props.user.name
  ? props.user.name.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase()
  : 'U'
</script>

<template>
  <div class="profile-page container-fluid px-4 py-4">
    <!-- Header -->
    <div class="mb-4">
      <h3 class="m-0 fw-bold text-primary d-flex align-items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
          <circle cx="12" cy="7" r="4"></circle>
        </svg>
        Pengaturan Profil Pengguna
      </h3>
      <p class="text-body-secondary m-0 mt-1">Ubah data identitas profil Anda serta kelola kata sandi akun Anda di sini.</p>
    </div>

    <div class="row g-4">
      <!-- Left Column: User Card -->
      <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 h-100 text-center p-4" style="background: white;">
          <div class="card-body d-flex flex-column align-items-center justify-content-center py-4">
            <!-- Initials Avatar -->
            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center font-monospace fw-bold mb-3 shadow-sm text-uppercase" style="width: 100px; height: 100px; font-size: 36px; border: 4px solid #eff6ff;">
              {{ initials }}
            </div>
            
            <h4 class="fw-bold text-dark mb-1">{{ user.name }}</h4>
            <p class="text-body-secondary small mb-3">{{ user.email }}</p>
            
            <!-- Role Badge -->
            <span class="badge text-uppercase px-3 py-2 rounded-pill mb-4" :class="user.role === 'admin' ? 'bg-danger-subtle text-danger border border-danger-subtle' : 'bg-success-subtle text-success border border-success-subtle'">
              {{ user.role === 'admin' ? 'Administrator' : 'Kasir / Staff' }}
            </span>
            
            <hr class="w-100 my-4 text-secondary opacity-25">
            
            <div class="w-100 d-flex flex-column gap-2 text-start px-2">
              <div class="d-flex justify-content-between text-body-secondary small">
                <span>Tanggal Terdaftar</span>
                <span class="fw-semibold text-dark">{{ new Date(user.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) }}</span>
              </div>
              <div class="d-flex justify-content-between text-body-secondary small">
                <span>Status Akun</span>
                <span class="fw-semibold text-success d-flex align-items-center gap-1">
                  <span class="bg-success rounded-circle" style="width: 8px; height: 8px; display: inline-block;"></span>
                  Aktif
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column: Edit Forms -->
      <div class="col-lg-8">
        <form @submit.prevent="updateProfile" class="d-flex flex-column gap-4">
          <!-- Card 1: Account Information -->
          <div class="card border-0 shadow-sm rounded-4" style="background: white;">
            <div class="card-header bg-white border-0 py-3 px-4">
              <h5 class="m-0 fw-bold text-dark d-flex align-items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-primary"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                Informasi Akun
              </h5>
            </div>
            <div class="card-body px-4 pb-4">
              <div class="row g-3">
                <div class="col-md-6">
                  <BaseInput
                    v-model="form.name"
                    label="Nama Lengkap"
                    placeholder="Masukkan nama lengkap Anda"
                    required
                    :error="form.errors.name"
                  />
                </div>
                <div class="col-md-6">
                  <BaseInput
                    v-model="form.email"
                    label="Alamat Email"
                    type="email"
                    placeholder="Masukkan alamat email Anda"
                    required
                    :error="form.errors.email"
                  />
                </div>
              </div>
            </div>
          </div>

          <!-- Card 2: Security / Password Change -->
          <div class="card border-0 shadow-sm rounded-4" style="background: white;">
            <div class="card-header bg-white border-0 py-3 px-4">
              <h5 class="m-0 fw-bold text-dark d-flex align-items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-primary"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                Ganti Kata Sandi (Opsional)
              </h5>
            </div>
            <div class="card-body px-4 pb-4">
              <div class="row g-3">
                <div class="col-md-12">
                  <BaseInput
                    v-model="form.current_password"
                    label="Password Saat Ini"
                    type="password"
                    placeholder="Masukkan password saat ini untuk memverifikasi ganti password"
                    :error="form.errors.current_password"
                  />
                </div>
                <div class="col-md-6">
                  <BaseInput
                    v-model="form.new_password"
                    label="Password Baru"
                    type="password"
                    placeholder="Masukkan password baru (min 8 karakter)"
                    :error="form.errors.new_password"
                  />
                </div>
                <div class="col-md-6">
                  <BaseInput
                    v-model="form.new_password_confirmation"
                    label="Konfirmasi Password Baru"
                    type="password"
                    placeholder="Masukkan kembali password baru"
                    :error="form.errors.new_password_confirmation"
                  />
                </div>
              </div>
            </div>
          </div>

          <!-- Action buttons -->
          <div class="d-flex justify-content-end gap-2">
            <button 
              type="submit" 
              class="btn btn-primary rounded-3 fw-bold d-flex align-items-center justify-content-center gap-2 px-4 py-2.5" 
              :disabled="form.processing || isSubmitting"
              style="box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);"
            >
              <span v-if="form.processing || isSubmitting" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
              <svg v-else xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
              Simpan Perubahan
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<style scoped>
.badge {
  font-weight: 600;
  letter-spacing: 0.2px;
}
:deep(.form-label) {
  font-weight: 600;
  font-size: 13.5px;
  color: var(--cui-secondary-color, #4f5d73);
}
</style>
