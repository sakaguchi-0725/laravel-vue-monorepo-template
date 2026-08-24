<script setup lang="ts">
import { useTodos } from '../model/use-todos'

const { status, rows, errorMessage, isLoading, statusFilterOptions } = useTodos()
</script>

<template>
  <main class="page">
    <h1 class="title">タスク一覧</h1>

    <div class="filter">
      <label class="label" for="status">ステータス</label>
      <select id="status" v-model="status" class="select">
        <option v-for="option in statusFilterOptions" :key="option.value" :value="option.value">
          {{ option.label }}
        </option>
      </select>
    </div>

    <p v-if="isLoading" class="notice">読み込み中です。</p>
    <p v-else-if="errorMessage" class="error" role="alert">{{ errorMessage }}</p>
    <p v-else-if="rows.length === 0" class="notice">タスクはまだありません。</p>
    <ul v-else class="list">
      <li v-for="row in rows" :key="row.id" class="item">
        <div class="item-head">
          <h2 class="item-title">{{ row.title }}</h2>
          <span class="item-status">{{ row.statusLabel }}</span>
        </div>
        <p v-if="row.description" class="item-description">{{ row.description }}</p>
        <p class="item-due">{{ row.dueOnLabel }}</p>
      </li>
    </ul>
  </main>
</template>

<style scoped>
.page {
  max-width: 40rem;
  margin-inline: auto;
  padding: 2rem 1rem;
}

.title {
  font-size: 1.5rem;
  font-weight: 700;
  margin-bottom: 1.5rem;
}

.filter {
  display: grid;
  gap: 0.375rem;
  margin-bottom: 1.5rem;
  justify-items: start;
}

.label {
  font-size: 0.875rem;
  font-weight: 600;
}

.select {
  padding: 0.5rem 0.75rem;
  border: 1px solid #d4d4d8;
  border-radius: 0.375rem;
  background-color: #ffffff;
}

.select:focus-visible {
  outline: 2px solid #6366f1;
  outline-offset: 1px;
}

.notice {
  font-size: 0.875rem;
  color: #52525b;
}

.error {
  font-size: 0.8125rem;
  color: #dc2626;
}

.list {
  display: grid;
  gap: 0.75rem;
  list-style: none;
  padding: 0;
}

.item {
  display: grid;
  gap: 0.375rem;
  padding: 1rem;
  border: 1px solid #e4e4e7;
  border-radius: 0.5rem;
}

.item-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
}

.item-title {
  font-size: 1rem;
  font-weight: 600;
}

.item-status {
  flex-shrink: 0;
  font-size: 0.75rem;
  padding: 0.125rem 0.5rem;
  border-radius: 999px;
  background-color: #f4f4f5;
  color: #3f3f46;
}

.item-description {
  font-size: 0.875rem;
  color: #52525b;
  white-space: pre-wrap;
}

.item-due {
  font-size: 0.8125rem;
  color: #71717a;
}
</style>
