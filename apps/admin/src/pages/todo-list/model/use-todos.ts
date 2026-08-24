import { computed, ref, watch } from 'vue'

import { client } from '@/shared/api'

import {
  statusFilterOptions,
  statusLabels,
  type Todo,
  type TodoRow,
  type TodoStatusFilter,
} from './types'

const toRow = (todo: Todo): TodoRow => ({
  id: todo.id,
  title: todo.title,
  description: todo.description ?? undefined,
  statusLabel: statusLabels[todo.status],
  dueOnLabel: todo.dueOn === null ? '期限なし' : `期限 ${todo.dueOn.replaceAll('-', '/')}`,
})

export const useTodos = () => {
  const status = ref<TodoStatusFilter>('all')
  const todos = ref<Todo[]>([])
  const errorMessage = ref<string>()
  const isLoading = ref(true)

  let latestRequestId = 0

  const fetchTodos = async () => {
    const requestId = latestRequestId + 1
    latestRequestId = requestId

    isLoading.value = true
    errorMessage.value = undefined

    const { data, error } = await client.GET('/admin/todos', {
      params: { query: status.value === 'all' ? {} : { status: status.value } },
    })

    if (requestId !== latestRequestId) return

    isLoading.value = false

    if (error) {
      todos.value = []
      errorMessage.value = error.message
      return
    }

    todos.value = data.todos
  }

  watch(status, fetchTodos, { immediate: true })

  const rows = computed(() => todos.value.map(toRow))

  return { status, rows, errorMessage, isLoading, statusFilterOptions }
}
