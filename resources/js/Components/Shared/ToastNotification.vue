<!-- Reusable Toast Notification -->
<template>
  <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999">
    <CToast
      v-for="toast in toasts"
      :key="toast.id"
      :color="toast.color"
      :autohide="true"
      :delay="3500"
      visible
      class="mb-2"
    >
      <CToastHeader closeButton>
        <CIcon :icon="toast.icon" class="me-2" />
        <strong class="me-auto">{{ toast.title }}</strong>
      </CToastHeader>
      <CToastBody>{{ toast.message }}</CToastBody>
    </CToast>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'

const page = usePage()
const toasts = ref([])
let idCounter = 0

watch(
  () => page.props.flash,
  (flash) => {
    if (flash?.success) addToast('success', flash.success)
    if (flash?.error)   addToast('danger', flash.error)
  },
  { immediate: true, deep: true }
)

function addToast(type, message) {
  const toast = {
    id: ++idCounter,
    color: type,
    icon: type === 'success' ? 'cil-check-circle' : 'cil-warning',
    title: type === 'success' ? 'Berhasil' : 'Error',
    message,
  }
  toasts.value.push(toast)
  setTimeout(() => {
    toasts.value = toasts.value.filter(t => t.id !== toast.id)
  }, 4000)
}
</script>
