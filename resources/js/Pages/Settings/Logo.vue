<script setup>
import { ref } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import DefaultLayout from '@/Layouts/DefaultLayout.vue'

defineOptions({ layout: DefaultLayout })

const page = usePage()
const { showSuccess, showError, showConfirm } = window

// Forms for uploading full and mini logos
const fullForm = useForm({
  logo_full: null,
})
const miniForm = useForm({
  logo_mini: null,
})

// Brand Name Form
const brandForm = useForm({
  brand_name: page.props.brand_name || 'POS Apps',
})

// File inputs and previews
const fullInput = ref(null)
const miniInput = ref(null)
const fullPreview = ref(null)
const miniPreview = ref(null)

// Drag and drop hover states
const isDraggingFull = ref(false)
const isDraggingMini = ref(false)

function triggerFullSelect() {
  fullInput.value.click()
}
function triggerMiniSelect() {
  miniInput.value.click()
}

function handleFullChange(e) {
  const file = e.target.files[0]
  if (!file) return
  if (!file.type.match('image.*')) {
    showError('File harus berupa gambar (png, jpg, jpeg, svg).')
    return
  }
  fullForm.logo_full = file
  fullPreview.value = URL.createObjectURL(file)
}

function handleMiniChange(e) {
  const file = e.target.files[0]
  if (!file) return
  if (!file.type.match('image.*')) {
    showError('File harus berupa gambar (png, jpg, jpeg, svg).')
    return
  }
  miniForm.logo_mini = file
  miniPreview.value = URL.createObjectURL(file)
}

// Drag & Drop Handlers
function onDragOverFull(e) {
  e.preventDefault()
  isDraggingFull.value = true
}
function onDragLeaveFull() {
  isDraggingFull.value = false
}
function onDropFull(e) {
  e.preventDefault()
  isDraggingFull.value = false
  const file = e.dataTransfer.files[0]
  if (!file) return
  if (!file.type.match('image.*')) {
    showError('File harus berupa gambar (png, jpg, jpeg, svg).')
    return
  }
  fullForm.logo_full = file
  fullPreview.value = URL.createObjectURL(file)
}

function onDragOverMini(e) {
  e.preventDefault()
  isDraggingMini.value = true
}
function onDragLeaveMini() {
  isDraggingMini.value = false
}
function onDropMini(e) {
  e.preventDefault()
  isDraggingMini.value = false
  const file = e.dataTransfer.files[0]
  if (!file) return
  if (!file.type.match('image.*')) {
    showError('File harus berupa gambar (png, jpg, jpeg, svg).')
    return
  }
  miniForm.logo_mini = file
  miniPreview.value = URL.createObjectURL(file)
}

// Update Brand Name
function updateBrandName() {
  if (!brandForm.brand_name || !brandForm.brand_name.trim()) {
    showError('Nama brand tidak boleh kosong.')
    return
  }
  
  brandForm.post('/settings/upload-logo', {
    onSuccess: () => {
      showSuccess('Nama brand berhasil diperbarui!')
    },
    onError: (err) => {
      showError(err.brand_name || 'Gagal memperbarui nama brand.')
    }
  })
}

// Reset Brand Name
async function resetBrand() {
  const confirmed = await showConfirm({
    title: 'Reset Nama Brand?',
    text: 'Anda yakin ingin mengembalikan Nama Brand ke default aplikasi?',
    confirmText: 'Ya, Reset',
    cancelText: 'Batal'
  })
  
  if (confirmed.isConfirmed) {
    const resetForm = useForm({ type: 'brand' })
    resetForm.post('/settings/reset-logo', {
      onSuccess: () => {
        brandForm.brand_name = page.props.logo?.brand_name || 'POS Apps'
        showSuccess('Nama brand dikembalikan ke default.')
      },
      onError: () => {
        showError('Gagal mengembalikan nama brand.')
      }
    })
  }
}

// Submissions using absolute routes to remain bulletproof
function uploadFullLogo() {
  if (!fullForm.logo_full) {
    showError('Silakan pilih berkas logo terlebih dahulu.')
    return
  }
  
  fullForm.post('/settings/upload-logo', {
    forceFormData: true,
    onSuccess: () => {
      fullPreview.value = null
      fullForm.reset()
      showSuccess('Logo penuh berhasil diperbarui!')
    },
    onError: (err) => {
      showError(err.logo_full || 'Gagal mengunggah logo.')
    }
  })
}

function uploadMiniLogo() {
  if (!miniForm.logo_mini) {
    showError('Silakan pilih berkas logo terlebih dahulu.')
    return
  }
  
  miniForm.post('/settings/upload-logo', {
    forceFormData: true,
    onSuccess: () => {
      miniPreview.value = null
      miniForm.reset()
      showSuccess('Logo ringkas berhasil diperbarui!')
    },
    onError: (err) => {
      showError(err.logo_mini || 'Gagal mengunggah logo.')
    }
  })
}

// Reset logo function
async function resetLogo(type) {
  const targetText = type === 'full' ? 'Logo Penuh' : type === 'mini' ? 'Logo Ringkas' : 'Semua Logo'
  const confirmed = await showConfirm({
    title: 'Kembalikan Logo?',
    text: `Anda yakin ingin mengembalikan ${targetText} ke logo default aplikasi?`,
    confirmText: 'Ya, Kembalikan',
    cancelText: 'Batal'
  })
  
  if (confirmed.isConfirmed) {
    const resetForm = useForm({ type })
    resetForm.post('/settings/reset-logo', {
      onSuccess: () => {
        if (type === 'full') fullPreview.value = null
        if (type === 'mini') miniPreview.value = null
        showSuccess('Logo berhasil dikembalikan ke default.')
      },
      onError: () => {
        showError('Gagal mengembalikan logo.')
      }
    })
  }
}
</script>

<template>
  <CRow class="g-4">
    <!-- Settings Panel (Logo Upload) -->
    <CCol :xs="12">
      <CCard class="border-0 shadow-sm rounded-4">
        <CCardHeader class="bg-white border-0 pt-4 pb-0 px-4">
          <div class="d-flex align-items-center gap-2">
            <span class="fs-4">🎨</span>
            <div>
              <h5 class="fw-bold text-dark m-0">Pengaturan Identitas Toko</h5>
              <small class="text-body-secondary">Sesuaikan nama brand dan gambar logo aplikasi POS Anda.</small>
            </div>
          </div>
        </CCardHeader>
        <CCardBody class="p-4">
          <div class="row g-4">
            
            <!-- 0. BRAND NAME CARD (TOP ROW) -->
            <div class="col-12">
              <div class="p-4 border border-light-subtle rounded-4 bg-light bg-opacity-50">
                <h6 class="fw-bold text-dark mb-2">Nama Brand Toko</h6>
                <p class="small text-secondary mb-3">
                  Teks nama brand yang ditampilkan di samping logo pada menu sidebar (maksimal 50 karakter).
                </p>
                <div class="row g-3 align-items-center">
                  <div class="col-md-8">
                    <input 
                      type="text" 
                      class="form-control form-control-lg rounded-3 border-light-subtle" 
                      v-model="brandForm.brand_name" 
                      placeholder="Contoh: VG Store" 
                      maxlength="50"
                    />
                  </div>
                  <div class="col-md-4 d-flex gap-2">
                    <button 
                      type="button" 
                      class="btn btn-primary fw-bold px-4 py-2 flex-grow-1 rounded-3" 
                      :disabled="brandForm.processing"
                      @click="updateBrandName"
                    >
                      <span v-if="brandForm.processing" class="spinner-border spinner-border-sm me-1"></span>
                      Simpan Nama
                    </button>
                    <button 
                      type="button" 
                      class="btn btn-outline-danger px-3 py-2 rounded-3" 
                      @click="resetBrand"
                      title="Reset Nama"
                    >
                      Reset
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- 1. LOGO PENUH (FULL LOGO) -->
            <div class="col-md-6">
              <div class="p-4 border border-light-subtle rounded-4 h-100 d-flex flex-column bg-light bg-opacity-50">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h6 class="fw-bold text-dark m-0">Gambar Logo (Expanded & Collapsed)</h6>
                  <button 
                    v-if="page.props.logo && page.props.logo.full"
                    type="button" 
                    class="btn btn-outline-danger btn-xs py-1 px-2 fw-semibold"
                    @click="resetLogo('full')"
                  >
                    Reset Default
                  </button>
                </div>
                <p class="small text-secondary mb-3">
                  Unggah gambar logo utama. Disarankan format PNG transparan atau SVG persegi dengan resolusi minimal 200x200px. Gambar ini otomatis dipotong melingkar (50% border-radius) di sidebar.
                </p>

                <!-- Drag & Drop Zone -->
                <div 
                  class="logo-dropzone flex-grow-1 d-flex flex-column align-items-center justify-content-center border-dashed rounded-4 p-4 text-center cursor-pointer mb-3"
                  :class="{ 'dragging': isDraggingFull }"
                  @click="triggerFullSelect"
                  @dragover="onDragOverFull"
                  @dragleave="onDragLeaveFull"
                  @drop="onDropFull"
                >
                  <input 
                    ref="fullInput" 
                    type="file" 
                    class="d-none" 
                    accept="image/*" 
                    @change="handleFullChange" 
                  />

                  <!-- Upload Icon/Text -->
                  <div v-if="!fullPreview" class="py-3">
                    <div class="fs-1 mb-2 opacity-50">📤</div>
                    <span class="fw-bold text-primary small">Pilih Berkas Gambar</span>
                    <span class="text-muted small d-block mt-1">atau tarik dan taruh berkas di sini</span>
                    <span class="badge bg-light text-secondary border mt-2" style="font-size: 10px;">Max 2MB</span>
                  </div>

                  <!-- Live Preview -->
                  <div v-else class="w-100 p-2 border rounded bg-white shadow-sm d-flex flex-column align-items-center">
                    <span class="badge bg-success mb-2">Pratinjau Baru</span>
                    <img :src="fullPreview" class="img-fluid rounded-circle mb-2" style="height: 80px; width: 80px; object-fit: cover; border: 2px solid #ddd;" />
                    <small class="text-secondary font-monospace">{{ fullForm.logo_full?.name }}</small>
                  </div>
                </div>

                <!-- Action Button -->
                <button 
                  type="button" 
                  class="btn btn-primary w-100 fw-bold py-2 rounded-3 mt-auto d-flex align-items-center justify-content-center gap-2"
                  :disabled="fullForm.processing || !fullForm.logo_full"
                  @click="uploadFullLogo"
                >
                  <span v-if="fullForm.processing" class="spinner-border spinner-border-sm"></span>
                  <span>Simpan Gambar Logo</span>
                </button>
              </div>
            </div>

            <!-- 2. LOGO MINI (COLLAPSED LOGO ALTERNATIF) -->
            <div class="col-md-6">
              <div class="p-4 border border-light-subtle rounded-4 h-100 d-flex flex-column bg-light bg-opacity-50">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h6 class="fw-bold text-dark m-0">Gambar Logo Mini Alternatif (Opsional)</h6>
                  <button 
                    v-if="page.props.logo && page.props.logo.mini"
                    type="button" 
                    class="btn btn-outline-danger btn-xs py-1 px-2 fw-semibold"
                    @click="resetLogo('mini')"
                  >
                    Reset Default
                  </button>
                </div>
                <p class="small text-secondary mb-3">
                  (Opsional) Unggah gambar berbeda khusus saat sidebar dalam posisi menciut/collapsed. Jika dibiarkan kosong, sistem otomatis memakai gambar logo utama di atas.
                </p>

                <!-- Drag & Drop Zone -->
                <div 
                  class="logo-dropzone flex-grow-1 d-flex flex-column align-items-center justify-content-center border-dashed rounded-4 p-4 text-center cursor-pointer mb-3"
                  :class="{ 'dragging': isDraggingMini }"
                  @click="triggerMiniSelect"
                  @dragover="onDragOverMini"
                  @dragleave="onDragLeaveMini"
                  @drop="onDropMini"
                >
                  <input 
                    ref="miniInput" 
                    type="file" 
                    class="d-none" 
                    accept="image/*" 
                    @change="handleMiniChange" 
                  />

                  <!-- Upload Icon/Text -->
                  <div v-if="!miniPreview" class="py-3">
                    <div class="fs-1 mb-2 opacity-50">📤</div>
                    <span class="fw-bold text-primary small">Pilih Berkas Gambar</span>
                    <span class="text-muted small d-block mt-1">atau tarik dan taruh berkas di sini</span>
                    <span class="badge bg-light text-secondary border mt-2" style="font-size: 10px;">Max 2MB</span>
                  </div>

                  <!-- Live Preview -->
                  <div v-else class="w-100 p-2 border rounded bg-white shadow-sm d-flex flex-column align-items-center">
                    <span class="badge bg-success mb-2">Pratinjau Baru</span>
                    <img :src="miniPreview" class="img-fluid rounded-circle mb-2" style="height: 80px; width: 80px; object-fit: cover; border: 2px solid #ddd;" />
                    <small class="text-secondary font-monospace">{{ miniForm.logo_mini?.name }}</small>
                  </div>
                </div>

                <!-- Action Button -->
                <button 
                  type="button" 
                  class="btn btn-primary w-100 fw-bold py-2 rounded-3 mt-auto d-flex align-items-center justify-content-center gap-2"
                  :disabled="miniForm.processing || !miniForm.logo_mini"
                  @click="uploadMiniLogo"
                >
                  <span v-if="miniForm.processing" class="spinner-border spinner-border-sm"></span>
                  <span>Simpan Logo Mini Alternatif</span>
                </button>
              </div>
            </div>

          </div>
        </CCardBody>
      </CCard>
    </CCol>
  </CRow>
</template>

<style scoped>
.border-dashed {
  border: 2px dashed #cbd5e1;
  transition: border-color 0.2s, background-color 0.2s, transform 0.2s;
}

.logo-dropzone {
  min-height: 200px;
  background-color: #ffffff;
}

.logo-dropzone:hover {
  border-color: #3b82f6;
  background-color: rgba(59, 130, 246, 0.02);
  transform: translateY(-2px);
}

.logo-dropzone.dragging {
  border-color: #10b981;
  background-color: rgba(16, 185, 129, 0.05);
}
</style>
