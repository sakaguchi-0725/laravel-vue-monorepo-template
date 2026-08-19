<script setup lang="ts">
import BaseButton from './base-button.vue'

defineProps<{
  size?: 'sm' | 'md'
  type?: 'button' | 'submit' | 'reset'
  disabled?: boolean
}>()
</script>

<template>
  <BaseButton variant="primary" :size="size" :type="type" :disabled="disabled">
    <slot />
  </BaseButton>
</template>
