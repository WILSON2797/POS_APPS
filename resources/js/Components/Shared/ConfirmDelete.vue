<!-- Reusable Confirm Delete Modal -->
<template>
  <CModal :visible="show" alignment="center" @close="$emit('close')">
    <CModalHeader>
      <CModalTitle>Konfirmasi Hapus</CModalTitle>
    </CModalHeader>
    <CModalBody>
      <div class="text-center py-2">
        <CIcon icon="cil-warning" size="3xl" class="text-danger mb-3" />
        <p class="mb-0">{{ message }}</p>
        <small class="text-body-secondary">Tindakan ini tidak dapat dibatalkan.</small>
      </div>
    </CModalBody>
    <CModalFooter>
      <CButton color="secondary" variant="outline" @click="$emit('close')">Batal</CButton>
      <CButton color="danger" :disabled="loading" @click="$emit('confirm')">
        <CSpinner v-if="loading" size="sm" class="me-1" />
        Ya, Hapus
      </CButton>
    </CModalFooter>
  </CModal>
</template>

<script setup>
defineProps({
  show:    { type: Boolean, default: false },
  message: { type: String, default: 'Apakah Anda yakin ingin menghapus data ini?' },
  loading: { type: Boolean, default: false },
})
defineEmits(['close', 'confirm'])
</script>
