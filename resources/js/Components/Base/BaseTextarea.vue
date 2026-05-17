<!-- BaseTextarea.vue -->
<template>
  <div class="mb-3">
    <label v-if="label" :for="id" class="form-label">
      {{ label }} <span v-if="required" class="text-danger">*</span>
    </label>
    <textarea
      :id="id"
      :value="modelValue"
      :placeholder="placeholder"
      :disabled="disabled"
      :rows="rows"
      :class="['form-control', { 'is-invalid': error }]"
      @input="$emit('update:modelValue', $event.target.value)"
    />
    <div v-if="error" class="invalid-feedback">{{ error }}</div>
  </div>
</template>

<script setup>
defineProps({
  modelValue: { default: '' },
  label:      { type: String, default: '' },
  placeholder:{ type: String, default: '' },
  error:      { type: String, default: '' },
  required:   { type: Boolean, default: false },
  disabled:   { type: Boolean, default: false },
  rows:       { type: Number, default: 3 },
  id:         { type: String, default: () => `ta-${Math.random().toString(36).slice(2)}` },
})
defineEmits(['update:modelValue'])
</script>
