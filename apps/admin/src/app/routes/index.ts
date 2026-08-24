import { createMemoryHistory, createRouter, createWebHistory } from 'vue-router'

import { todoRoutes } from './todo'

export const createAppRouter = (mode: 'web' | 'memory') => {
  const history = mode === 'web' ? createWebHistory() : createMemoryHistory()

  return createRouter({
    history,
    routes: [
      {
        path: '/',
        redirect: { name: 'todo.list' },
      },
      ...todoRoutes,
    ],
  })
}
