<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

import { logo } from '@/assets/brand/logo'
import { sygnet } from '@/assets/brand/sygnet'
import { AppSidebarNav } from '@/Components/AppSidebarNav.js'
import { useSidebarStore } from '@/stores/sidebar.js'

const sidebar = useSidebarStore()
const page = usePage()

// Establish reactive computed variables to instantly watch Inertia page props
const logoFull = computed(() => page.props.logo?.full)
const logoMini = computed(() => page.props.logo?.mini || page.props.logo?.full)
const brandName = computed(() => page.props.logo?.brand_name || 'POS Apps')
</script>

<template>
  <CSidebar
    class="border-end"
    colorScheme="dark"
    position="fixed"
    :unfoldable="sidebar.unfoldable"
    :visible="sidebar.visible"
    @visible-change="(value) => sidebar.toggleVisible(value)"
  >
    <CSidebarHeader class="border-bottom">
      <Link href="/dashboard" class="sidebar-brand d-flex align-items-center">
        <!-- Render custom uploaded full logo if exists, else fallback to premium CoreUI SVG -->
        <template v-if="logoFull">
          <div class="sidebar-brand-full d-flex align-items-center">
            <img :src="logoFull" style="height: 34px; width: 34px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(255, 255, 255, 0.25);" alt="Logo Penuh" />
            <span class="ms-2 fw-bold text-white fs-5 brand-text" style="letter-spacing: 0.5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 135px;">{{ brandName }}</span>
          </div>
        </template>
        <template v-else>
          <CIcon custom-class-name="sidebar-brand-full" :icon="logo" :height="32" />
        </template>

        <!-- Render custom uploaded mini logo if exists, else fallback to premium CoreUI SVG -->
        <template v-if="logoMini">
          <img :src="logoMini" class="sidebar-brand-narrow" style="height: 32px; width: 32px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(255, 255, 255, 0.25);" alt="Logo Ringkas" />
        </template>
        <template v-else>
          <CIcon custom-class-name="sidebar-brand-narrow" :icon="sygnet" :height="32" />
        </template>
      </Link>
      <CCloseButton class="d-lg-none" dark @click="sidebar.toggleVisible()" />
    </CSidebarHeader>
    <AppSidebarNav />
    <CSidebarFooter class="border-top d-none d-lg-flex">
      <CSidebarToggler @click="sidebar.toggleUnfoldable()" />
    </CSidebarFooter>
  </CSidebar>
</template>

<style scoped>
.brand-text {
  font-family: 'Outfit', 'Inter', -apple-system, sans-serif;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
}
</style>
