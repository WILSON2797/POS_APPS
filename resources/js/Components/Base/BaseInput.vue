<!-- BaseInput.vue - Reusable input with label, error, hint -->
<template>
  <div class="mb-3">
    <label v-if="label" :for="id" class="form-label">
      {{ label }} <span v-if="required" class="text-danger">*</span>
    </label>
    <input
      :id="id"
      :type="type"
      :value="modelValue"
      :placeholder="placeholder"
      :disabled="disabled"
      :class="['form-control', { 'is-invalid': error, 'form-control-sm': size === 'sm' }]"
      v-bind="$attrs"
      @input="$emit('update:modelValue', $event.target.value)"
    />
    <div v-if="error" class="invalid-feedback">{{ error }}</div>
    <div v-if="hint" class="form-text text-body-secondary">{{ hint }}</div>
  </div>
</template>

<script setup>
defineOptions({ inheritAttrs: false })
defineProps({
  modelValue: { default: '' },
  label:      { type: String, default: '' },
  type:       { type: String, default: 'text' },
  placeholder:{ type: String, default: '' },
  error:      { type: String, default: '' },
  hint:       { type: String, default: '' },
  required:   { type: Boolean, default: false },
  disabled:   { type: Boolean, default: false },
  size:       { type: String, default: '' },
  id:         { type: String, default: () => `input-${Math.random().toString(36).slice(2)}` },
})
defineEmits(['update:modelValue'])
</script>
