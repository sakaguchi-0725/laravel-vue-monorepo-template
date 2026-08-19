import { createRouter, createWebHistory } from 'vue-router'

import { ExamplePage } from '@/pages/example'

export const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      name: 'example',
      component: ExamplePage,
    },
  ],
})
