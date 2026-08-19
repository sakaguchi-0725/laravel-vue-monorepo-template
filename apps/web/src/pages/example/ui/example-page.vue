<script setup lang="ts">
import { toTypedSchema } from '@vee-validate/zod'
import { useForm } from 'vee-validate'
import { ref } from 'vue'

import { schema, type Form } from '../model/schema'

const { defineField, errors, handleSubmit } = useForm<Form>({
  validationSchema: toTypedSchema(schema),
})

const [name, nameAttrs] = defineField('name')
const [email, emailAttrs] = defineField('email')

const submitted = ref<Form>()

const onSubmit = handleSubmit((values) => {
  submitted.value = values
})
</script>

<template>
  <main class="page">
    <h1 class="title">サンプルフォーム</h1>

    <form class="form" novalidate @submit="onSubmit">
      <div class="field">
        <label class="label" for="name">名前</label>
        <input id="name" v-model="name" v-bind="nameAttrs" class="input" type="text" />
        <p v-if="errors.name" class="error">{{ errors.name }}</p>
      </div>

      <div class="field">
        <label class="label" for="email">メールアドレス</label>
        <input id="email" v-model="email" v-bind="emailAttrs" class="input" type="email" />
        <p v-if="errors.email" class="error">{{ errors.email }}</p>
      </div>

      <button class="submit" type="submit">送信</button>
    </form>

    <p v-if="submitted" class="result">
      送信しました: {{ submitted.name }}（{{ submitted.email }}）
    </p>
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

.input {
  padding: 0.5rem 0.75rem;
  border: 1px solid #d4d4d8;
  border-radius: 0.375rem;
}

.input:focus-visible {
  outline: 2px solid #6366f1;
  outline-offset: 1px;
}

.error {
  font-size: 0.8125rem;
  color: #dc2626;
}

.submit {
  justify-self: start;
  padding: 0.5rem 1.25rem;
  border: none;
  border-radius: 0.375rem;
  background-color: #4f46e5;
  color: #fff;
  font-weight: 600;
}

.result {
  margin-top: 1.5rem;
  font-size: 0.875rem;
}
</style>
