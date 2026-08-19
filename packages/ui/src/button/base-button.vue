<script setup lang="ts">
const {
  variant,
  size = 'md',
  type = 'button',
  disabled = false,
} = defineProps<{
  variant: 'primary' | 'secondary' | 'danger'
  size?: 'sm' | 'md'
  type?: 'button' | 'submit' | 'reset'
  disabled?: boolean
}>()
</script>

<template>
  <button :class="['button', variant, size]" :type="type" :disabled="disabled">
    <slot />
  </button>
</template>

<style scoped>
.button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  border: 1px solid transparent;
  border-radius: 0.375rem;
  font-weight: 600;
  transition:
    background-color 0.15s,
    border-color 0.15s;
}

.button:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.sm {
  padding: 0.25rem 0.75rem;
  font-size: 0.8125rem;
}

.md {
  padding: 0.5rem 1.25rem;
  font-size: 0.875rem;
}

.primary {
  background-color: #4f46e5;
  color: #fff;
}

.primary:not(:disabled):hover {
  background-color: #4338ca;
}

.secondary {
  background-color: #fff;
  border-color: #d4d4d8;
  color: #3f3f46;
}

.secondary:not(:disabled):hover {
  background-color: #fafafa;
}

.danger {
  background-color: #dc2626;
  color: #fff;
}

.danger:not(:disabled):hover {
  background-color: #b91c1c;
}
</style>
