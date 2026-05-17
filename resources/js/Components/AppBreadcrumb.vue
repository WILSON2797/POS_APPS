<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

const page = usePage()

const breadcrumbs = computed(() => {
  const url = page.url
  const segments = url.split('/').filter(Boolean)
  const items = [{ label: 'Home', href: '/dashboard' }]

  let path = ''
  segments.forEach((segment) => {
    path += `/${segment}`
    items.push({
      label: segment.charAt(0).toUpperCase() + segment.slice(1).replace(/-/g, ' '),
      href: path,
    })
  })

  return items
})
</script>

<template>
  <CBreadcrumb class="my-0">
    <CBreadcrumbItem
      v-for="(item, index) in breadcrumbs"
      :key="index"
      :href="index < breadcrumbs.length - 1 ? item.href : undefined"
      :active="index === breadcrumbs.length - 1"
    >
      {{ item.label }}
    </CBreadcrumbItem>
  </CBreadcrumb>
</template>