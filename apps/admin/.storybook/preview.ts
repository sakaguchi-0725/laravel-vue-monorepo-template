import type { Preview } from '@storybook/vue3-vite'
import { initialize, mswLoader } from 'msw-storybook-addon'

import { handlers } from '@/shared/api/mocks'

import '@/app/config/zod'
import '@/app/assets/index.css'

initialize(
  {
    onUnhandledRequest: (request, print) => {
      if (new URL(request.url).origin === window.location.origin) return
      print.warning()
    },
  },
  handlers,
)

const preview: Preview = {
  loaders: [mswLoader],
}

export default preview
