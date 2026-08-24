import { TodoListPage } from '@/pages/todo-list'
import type { RouteRecordRaw } from 'vue-router'

export const todoRoutes: RouteRecordRaw[] = [
  {
    path: '/todos',
    name: 'todo.list',
    component: TodoListPage,
  },
]
