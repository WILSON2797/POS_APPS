<script setup>
import { CContainer } from '@coreui/vue'
import AppFooter from '@/Components/AppFooter.vue'
import AppHeader from '@/Components/AppHeader.vue'
import AppSidebar from '@/Components/AppSidebar.vue'
import ToastNotification from '@/Components/Shared/ToastNotification.vue'
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const page = usePage()
const isFluid = computed(() => {
  return page.component === 'POS/Index'
})
</script>

<template>
  <div>
    <AppSidebar />
    <div class="wrapper d-flex flex-column min-vh-100">
      <AppHeader />
      <div class="body flex-grow-1">
        <CContainer class="px-4" :fluid="isFluid" :lg="!isFluid">
          <slot />
        </CContainer>
      </div>
      <AppFooter />
    </div>
    <ToastNotification />
  </div>
</template>
