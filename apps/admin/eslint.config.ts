import { defineAppConfig } from '@repo/eslint-config'

export default defineAppConfig(new URL('.', import.meta.url).pathname)
