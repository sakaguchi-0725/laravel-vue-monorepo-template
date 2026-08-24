<script setup lang="ts">
import { useRoute } from 'vue-router'

const route = useRoute()
</script>

<template>
  <div class="bar">
    <span class="label">ROUTE</span>

    <div class="box">
      <span aria-label="route" class="path">{{ route.fullPath }}</span>
    </div>
  </div>

  <slot />
</template>

<style scoped>
.bar {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 10px;
  background: #f2f2ee;
  border-bottom: 1px solid #e4e4dd;
  font-family: ui-monospace, monospace;
}

.label {
  font-size: 10px;
  font-weight: 600;
  letter-spacing: 0.1em;
  color: #8a8a82;
}

.box {
  flex: 0 1 auto;
  min-width: 180px;
  max-width: 420px;
  display: flex;
  align-items: center;
  padding: 5px 10px;
  background: #fff;
  border: 1px solid #dededa;
  border-radius: 6px;
  font-size: 12px;
}

.path {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
</style>
