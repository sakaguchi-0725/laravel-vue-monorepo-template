import js from '@eslint/js'
import skipFormatting from '@vue/eslint-config-prettier/skip-formatting'
import { vueTsConfigs, withVueTs } from '@vue/eslint-config-typescript'
import { globalIgnores } from 'eslint/config'
import pluginVue from 'eslint-plugin-vue'
import globals from 'globals'

import { defineFsdConfig } from './fsd.ts'

type ConfigInput = Parameters<typeof withVueTs>[1]

export const defineAppConfig = (rootDir: string, ...overrides: ConfigInput[]) =>
  withVueTs(
    { rootDir },
    globalIgnores([
      '**/dist/**',
      '**/dist-ssr/**',
      '**/coverage/**',
      '**/storybook-static/**',
      'src/shared/api/schema.ts',
    ]),
    { files: ['**/*.{ts,mts,tsx,vue}'] },
    js.configs.recommended,
    pluginVue.configs['flat/recommended'],
    vueTsConfigs.recommendedTypeChecked,
    {
      languageOptions: {
        globals: globals.browser,
        parserOptions: { tsconfigRootDir: rootDir },
      },
    },
    skipFormatting,
    {
      rules: {
        '@typescript-eslint/no-explicit-any': 'error',
        'no-restricted-syntax': [
          'error',
          {
            selector: 'TSEnumDeclaration',
            message: 'Use an `as const` object with a union type instead of `enum`.',
          },
        ],
        'func-style': ['error', 'expression'],
        'prefer-arrow-callback': 'error',
        'vue/component-name-in-template-casing': ['error', 'PascalCase'],
      },
    },
    ...defineFsdConfig(rootDir),
    ...overrides,
  )
