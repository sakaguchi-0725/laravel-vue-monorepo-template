import { setup, type Preview } from '@storybook/vue3-vite'
import { initialize, mswLoader } from 'msw-storybook-addon'
import { h } from 'vue'

import { createAppRouter } from '@/app/routes'
import { handlers } from '@/shared/api/mocks'
import { RouteBar } from '@/shared/test'

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

setup(async (app, context) => {
  const router = createAppRouter('memory')

  app.use(router)
  await router.replace(context?.parameters.initialRoute ?? '/')
})

const preview: Preview = {
  parameters: { layout: 'fullscreen' },
  loaders: [mswLoader],
  decorators: [(story) => () => h(RouteBar, null, { default: () => h(story()) })],
}

export default preview
