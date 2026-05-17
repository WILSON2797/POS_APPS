<!-- Reusable Pagination Component -->
<template>
  <div v-if="meta.last_page > 1" class="d-flex justify-content-between align-items-center mt-3">
    <small class="text-body-secondary">
      Menampilkan {{ meta.from }}–{{ meta.to }} dari {{ meta.total }} data
    </small>
    <CPagination>
      <CPaginationItem
        :disabled="meta.current_page === 1"
        @click="changePage(meta.current_page - 1)"
        aria-label="Previous"
      >
        <span aria-hidden="true">&laquo;</span>
      </CPaginationItem>
      <CPaginationItem
        v-for="page in visiblePages"
        :key="page"
        :active="page === meta.current_page"
        @click="changePage(page)"
      >
        {{ page }}
      </CPaginationItem>
      <CPaginationItem
        :disabled="meta.current_page === meta.last_page"
        @click="changePage(meta.current_page + 1)"
        aria-label="Next"
      >
        <span aria-hidden="true">&raquo;</span>
      </CPaginationItem>
    </CPagination>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  meta: { type: Object, required: true },
})

const visiblePages = computed(() => {
  const { current_page, last_page } = props.meta
  const range = []
  const start = Math.max(1, current_page - 2)
  const end = Math.min(last_page, current_page + 2)
  for (let i = start; i <= end; i++) range.push(i)
  return range
})

function changePage(page) {
  router.get(window.location.pathname, { ...Object.fromEntries(new URLSearchParams(window.location.search)), page }, { preserveState: true, preserveScroll: true })
}
</script>
