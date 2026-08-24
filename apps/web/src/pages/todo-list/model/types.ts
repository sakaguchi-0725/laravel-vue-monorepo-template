import type { ApiSchema } from '@/shared/api'

export type Todo = ApiSchema['Todo']

export type TodoStatus = ApiSchema['TodoStatus']

export type TodoStatusFilter = TodoStatus | 'all'

export type TodoStatusFilterOption = {
  value: TodoStatusFilter
  label: string
}

export type TodoRow = {
  id: number
  title: string
  description: string | undefined
  statusLabel: string
  dueOnLabel: string
}

export const statusLabels: Record<TodoStatus, string> = {
  pending: '未完了',
  done: '完了',
}

export const statusFilterOptions: readonly TodoStatusFilterOption[] = [
  { value: 'all', label: 'すべて' },
  { value: 'pending', label: '未完了' },
  { value: 'done', label: '完了' },
]
