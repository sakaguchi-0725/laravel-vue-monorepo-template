<script setup lang="ts">
import { useRouter } from 'vue-router'

import { PrimaryButton } from '@repo/ui'

import { useTodoForm } from '../model/use-todo-form'

const router = useRouter()

const { defineField, errors, onSubmit, isSubmitting, errorMessage } = useTodoForm(() => {
  void router.push({ name: 'todo.list' })
})

const [title, titleAttrs] = defineField('title')
const [description, descriptionAttrs] = defineField('description')
const [dueOn, dueOnAttrs] = defineField('dueOn')
</script>

<template>
  <main class="page">
    <h1 class="title">タスクを作成</h1>

    <form class="form" novalidate @submit="onSubmit">
      <div class="field">
        <label class="label" for="title">件名</label>
        <input id="title" v-model="title" v-bind="titleAttrs" class="input" type="text" />
        <p v-if="errors.title" class="error" role="alert">{{ errors.title }}</p>
      </div>

      <div class="field">
        <label class="label" for="description">詳細説明</label>
        <textarea
          id="description"
          v-model="description"
          v-bind="descriptionAttrs"
          class="textarea"
          rows="4"
        />
        <p v-if="errors.description" class="error" role="alert">{{ errors.description }}</p>
      </div>

      <div class="field">
        <label class="label" for="dueOn">期限日</label>
        <input id="dueOn" v-model="dueOn" v-bind="dueOnAttrs" class="input" type="date" />
        <p v-if="errors.dueOn" class="error" role="alert">{{ errors.dueOn }}</p>
      </div>

      <p v-if="errorMessage" class="error" role="alert">{{ errorMessage }}</p>

      <PrimaryButton type="submit" :disabled="isSubmitting">作成</PrimaryButton>
    </form>
  </main>
</template>

<style scoped>
.page {
  max-width: 32rem;
  margin-inline: auto;
  padding: 2rem 1rem;
}

.title {
  font-size: 1.5rem;
  font-weight: 700;
  margin-bottom: 1.5rem;
}

.form {
  display: grid;
  gap: 1.25rem;
}

.field {
  display: grid;
  gap: 0.375rem;
}

.label {
  font-size: 0.875rem;
  font-weight: 600;
}

.input,
.textarea {
  padding: 0.5rem 0.75rem;
  border: 1px solid #d4d4d8;
  border-radius: 0.375rem;
  font: inherit;
}

.textarea {
  resize: vertical;
}

.input:focus-visible,
.textarea:focus-visible {
  outline: 2px solid #6366f1;
  outline-offset: 1px;
}

.error {
  font-size: 0.8125rem;
  color: #dc2626;
}

.form :deep(.button) {
  justify-self: start;
}
</style>
