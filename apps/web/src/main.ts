import { createApp } from 'vue'

import App from '@/app/app.vue'
import { createAppRouter } from '@/app/routes'

import '@/app/config/zod'
import '@/app/assets/index.css'

createApp(App).use(createAppRouter('web')).mount('#app')
