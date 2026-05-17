<script setup>
import { ref } from 'vue'
import { useForm, Head } from '@inertiajs/vue3'

const form = useForm({
  email: '',
  password: '',
  remember: false,
})

const submit = () => {
  form.post('/login', {
    onFinish: () => form.reset('password'),
  })
}
</script>

<template>
  <Head title="Login" />
  <div class="wrapper min-vh-100 d-flex flex-row align-items-center">
    <CContainer>
      <CRow class="justify-content-center">
        <CCol :md="6" :lg="5">
          <CCard class="shadow-lg border-0">
            <CCardBody class="p-4">
              <CForm @submit.prevent="submit">
                <div class="text-center mb-4">
                  <h1 class="h3">POS Apps</h1>
                  <p class="text-body-secondary">Masuk ke akun Anda</p>
                </div>

                <div v-if="form.errors.email" class="alert alert-danger py-2 small">
                  {{ form.errors.email }}
                </div>

                <CInputGroup class="mb-3">
                  <CInputGroupText>
                    <CIcon icon="cil-envelope-closed" />
                  </CInputGroupText>
                  <CFormInput
                    v-model="form.email"
                    type="email"
                    placeholder="Email"
                    autocomplete="email"
                    required
                  />
                </CInputGroup>

                <CInputGroup class="mb-3">
                  <CInputGroupText>
                    <CIcon icon="cil-lock-locked" />
                  </CInputGroupText>
                  <CFormInput
                    v-model="form.password"
                    type="password"
                    placeholder="Password"
                    autocomplete="current-password"
                    required
                  />
                </CInputGroup>

                <CFormCheck
                  v-model="form.remember"
                  class="mb-3"
                  label="Ingat saya"
                />

                <CButton
                  type="submit"
                  color="primary"
                  class="w-100 mb-3"
                  :disabled="form.processing"
                >
                  <CSpinner v-if="form.processing" size="sm" class="me-2" />
                  Masuk
                </CButton>
              </CForm>
            </CCardBody>
          </CCard>
        </CCol>
      </CRow>
    </CContainer>
  </div>
</template>
