import { createMemoryHistory, createRouter, createWebHistory } from 'vue-router'

import { ExamplePage } from '@/pages/example'
import { todoRoutes } from './todo'

export const createAppRouter = (mode: 'web' | 'memory') => {
  const history = mode === 'web' ? createWebHistory() : createMemoryHistory()

  return createRouter({
    history,
    routes: [
      {
        path: '/',
        name: 'example',
        component: ExamplePage,
      },
      ...todoRoutes,
    ],
  })
}
