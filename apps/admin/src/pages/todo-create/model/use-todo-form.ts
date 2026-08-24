import { toTypedSchema } from '@vee-validate/zod'
import { useForm } from 'vee-validate'
import { ref } from 'vue'

import { client } from '@/shared/api'

import { schema, type Form } from './schema'
import type { CreateTodoRequest } from './types'

const toBody = (values: Form): CreateTodoRequest => ({
  title: values.title,
  description: values.description === '' ? undefined : values.description,
  dueOn: values.dueOn === '' ? undefined : values.dueOn,
})

export const useTodoForm = (onCreated: () => void) => {
  const { defineField, errors, handleSubmit, isSubmitting } = useForm<Form>({
    validationSchema: toTypedSchema(schema),
    initialValues: { title: '', description: '', dueOn: '' },
  })

  const errorMessage = ref<string>()

  const onSubmit = handleSubmit(async (values) => {
    errorMessage.value = undefined

    const { error } = await client.POST('/admin/todos', { body: toBody(values) })

    if (error) {
      errorMessage.value = error.message
      return
    }

    onCreated()
  })

  return { defineField, errors, onSubmit, isSubmitting, errorMessage }
}
