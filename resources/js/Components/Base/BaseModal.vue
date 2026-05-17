<!-- BaseModal.vue - Reusable modal using native Bootstrap modal -->
<template>
  <Teleport to="body">
    <div
      v-if="show"
      class="modal fade show d-block"
      tabindex="-1"
      style="background:rgba(0,0,0,.5)"
      @click.self="$emit('close')"
    >
      <div :class="['modal-dialog', `modal-${size}`, 'modal-dialog-centered', 'modal-dialog-scrollable']">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">{{ title }}</h5>
            <button type="button" class="btn-close" @click="$emit('close')" />
          </div>
          <div class="modal-body">
            <slot />
          </div>
          <div v-if="$slots.footer || showFooter" class="modal-footer">
            <slot name="footer">
              <BaseButton variant="secondary" @click="$emit('close')">Batal</BaseButton>
              <BaseButton :variant="confirmVariant" :loading="loading" @click="$emit('confirm')">
                {{ confirmText }}
              </BaseButton>
            </slot>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import BaseButton from '@/Components/Base/BaseButton.vue'

defineProps({
  show:           { type: Boolean, default: false },
  title:          { type: String, default: '' },
  size:           { type: String, default: 'lg' },
  confirmText:    { type: String, default: 'Simpan' },
  confirmVariant: { type: String, default: 'primary' },
  loading:        { type: Boolean, default: false },
  showFooter:     { type: Boolean, default: true },
})
defineEmits(['close', 'confirm'])
</script>
