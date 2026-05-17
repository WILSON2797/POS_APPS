<!-- Reusable Modal Component -->
<template>
  <CModal
    :visible="show"
    :title="title"
    size="lg"
    alignment="center"
    @close="$emit('close')"
  >
    <CModalHeader>
      <CModalTitle>{{ title }}</CModalTitle>
    </CModalHeader>
    <CModalBody>
      <slot />
    </CModalBody>
    <CModalFooter>
      <CButton color="secondary" variant="outline" @click="$emit('close')">
        Batal
      </CButton>
      <CButton :color="confirmColor" :disabled="loading" @click="$emit('confirm')">
        <CSpinner v-if="loading" size="sm" class="me-1" />
        {{ confirmText }}
      </CButton>
    </CModalFooter>
  </CModal>
</template>

<script setup>
defineProps({
  show:        { type: Boolean, default: false },
  title:       { type: String, default: 'Modal' },
  confirmText: { type: String, default: 'Simpan' },
  confirmColor:{ type: String, default: 'primary' },
  loading:     { type: Boolean, default: false },
})
defineEmits(['close', 'confirm'])
</script>
