import type { RouteRecordRaw } from 'vue-router'

import { TodoCreatePage } from '@/pages/todo-create'
import { TodoListPage } from '@/pages/todo-list'

export const todoRoutes: RouteRecordRaw[] = [
  {
    path: '/todos',
    name: 'todo.list',
    component: TodoListPage,
  },
  {
    path: '/todos/new',
    name: 'todo.create',
    component: TodoCreatePage,
  },
]
