import type { StorybookConfig } from '@storybook/vue3-vite'

const config: StorybookConfig = {
  framework: {
    name: '@storybook/vue3-vite',
    options: {
      docgen: { plugin: 'vue-component-meta', tsconfig: 'tsconfig.app.json' },
    },
  },
  stories: ['../src/**/*.stories.ts'],
  addons: ['@storybook/addon-vitest'],
}

export default config
