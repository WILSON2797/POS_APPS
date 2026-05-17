<script setup>
import DefaultLayout from '@/Layouts/DefaultLayout.vue'

defineOptions({ layout: DefaultLayout })
</script>

<template>
  <CRow>
    <CCol :xs="12">
      <CCard class="mb-4">
        <CCardBody>
          <h4 class="card-title mb-0">Dashboard</h4>
          <div class="small text-body-secondary">POS Application</div>
        </CCardBody>
      </CCard>
    </CCol>
  </CRow>
</template>
