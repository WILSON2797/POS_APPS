<!-- BaseDatePicker.vue - Powered by @vuepic/vue-datepicker -->
<template>
  <div class="mb-3">
    <label v-if="label" class="form-label">
      {{ label }} <span v-if="required" class="text-danger">*</span>
    </label>
    <VueDatePicker
      :modelValue="modelValue"
      :range="range"
      :month-picker="monthPicker"
      :enable-time-picker="false"
      :format="format"
      :placeholder="placeholder"
      :disabled="disabled"
      auto-apply
      locale="id"
      :class="{ 'dp--invalid': error }"
      @update:modelValue="$emit('update:modelValue', $event)"
    />
    <div v-if="error" class="text-danger small mt-1">{{ error }}</div>
  </div>
</template>

<script setup>
import VueDatePicker from '@vuepic/vue-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'

defineProps({
  modelValue:  { default: null },
  label:       { type: String, default: '' },
  placeholder: { type: String, default: 'Pilih tanggal...' },
  range:       { type: Boolean, default: false },
  monthPicker: { type: Boolean, default: false },
  format:      { type: String, default: 'dd/MM/yyyy' },
  disabled:    { type: Boolean, default: false },
  required:    { type: Boolean, default: false },
  error:       { type: String, default: '' },
})
defineEmits(['update:modelValue'])
</script>
