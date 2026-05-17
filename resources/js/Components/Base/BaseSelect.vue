<!-- BaseSelect.vue - Powered by @vueform/multiselect -->
<template>
  <div class="mb-3">
    <label v-if="label" class="form-label">
      {{ label }} <span v-if="required" class="text-danger">*</span>
    </label>
    <Multiselect
      v-bind="$attrs"
      :modelValue="modelValue"
      :options="options"
      :label="optionLabel"
      :valueProp="optionValue"
      :placeholder="placeholder"
      :searchable="searchable"
      :clearable="clearable"
      :disabled="disabled"
      :class="{ 'is-invalid-ms': error }"
      @update:modelValue="$emit('update:modelValue', $event)"
    />
    <div v-if="error" class="text-danger small mt-1">{{ error }}</div>
  </div>
</template>

<script setup>
import Multiselect from '@vueform/multiselect'
import '@vueform/multiselect/themes/default.css'

defineOptions({ inheritAttrs: false })
defineProps({
  modelValue:  { default: null },
  options:     { type: Array, default: () => [] },
  label:       { type: String, default: '' },
  optionLabel: { type: String, default: 'name' },
  optionValue: { type: String, default: 'id' },
  placeholder: { type: String, default: 'Pilih...' },
  searchable:  { type: Boolean, default: true },
  clearable:   { type: Boolean, default: true },
  disabled:    { type: Boolean, default: false },
  required:    { type: Boolean, default: false },
  error:       { type: String, default: '' },
})
defineEmits(['update:modelValue'])
</script>

<style>
.is-invalid-ms .multiselect-wrapper { border-color: #dc3545; }
</style>
