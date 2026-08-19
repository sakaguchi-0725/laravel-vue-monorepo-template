import { createApp } from 'vue'

import App from '@/app/app.vue'
import { router } from '@/app/routes'

import '@/app/config/zod'
import '@/app/assets/index.css'

createApp(App).use(router).mount('#app')
