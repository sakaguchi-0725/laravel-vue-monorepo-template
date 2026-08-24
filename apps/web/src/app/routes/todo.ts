import type { RouteRecordRaw } from 'vue-router'

import { TodoListPage } from '@/pages/todo-list'

export const todoRoutes: RouteRecordRaw[] = [
  {
    path: '/todos',
    name: 'todo.list',
    component: TodoListPage,
  },
]
