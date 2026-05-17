<!-- BaseButton.vue -->
<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    :class="[
      'btn',
      `btn-${variant}`,
      size ? `btn-${size}` : '',
      { 'w-100': block }
    ]"
    v-bind="$attrs"
    @click="$emit('click', $event)"
  >
    <span v-if="loading" class="spinner-border spinner-border-sm me-1" role="status" />
    <slot />
  </button>
</template>

<script setup>
defineOptions({ inheritAttrs: false })
defineProps({
  type:     { type: String, default: 'button' },
  variant:  { type: String, default: 'primary' },
  size:     { type: String, default: '' },
  loading:  { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  block:    { type: Boolean, default: false },
})
defineEmits(['click'])
</script>
