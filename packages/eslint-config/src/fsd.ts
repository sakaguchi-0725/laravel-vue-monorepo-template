import path from 'node:path'

import { createConfig } from 'eslint-plugin-boundaries/config'

const DIRECTION_MESSAGE =
  'FSD dependency direction violated: `{{from.element.type}}` must not import `{{to.element.type}}`. Allowed direction is app -> pages -> features -> shared.'

const SLICE_MESSAGE =
  'FSD slice isolation violated: `{{from.element.type}}/{{from.element.captured.slice}}` must not import the sibling slice `{{to.element.type}}/{{to.element.captured.slice}}`.'

const defineResolverConfig = (rootDir: string) => ({
  name: 'fsd/resolver',
  settings: {
    'import/resolver': {
      typescript: {
        project: path.join(rootDir, 'tsconfig.app.json'),
        extensions: ['.ts', '.tsx', '.vue', '.js'],
      },
    },
  },
})

export const defineFsdConfig = (rootDir: string) => [
  defineResolverConfig(rootDir),
  createConfig({
    name: 'fsd',
    files: ['**/*.{ts,mts,tsx,vue}'],
    settings: {
      'boundaries/root-path': rootDir,
      'boundaries/elements': [
        { type: 'app', pattern: 'src/app' },
        { type: 'pages', pattern: 'src/pages/*', capture: ['slice'] },
        { type: 'features', pattern: 'src/features/*', capture: ['slice'] },
        { type: 'shared', pattern: 'src/shared' },
      ],
    },
    rules: {
      'boundaries/dependencies': [
        'error',
        {
          default: 'disallow',
          message: DIRECTION_MESSAGE,
          policies: [
            {
              from: { element: { type: 'app' } },
              allow: { to: { element: { type: ['pages', 'features', 'shared'] } } },
            },
            {
              from: { element: { type: 'pages' } },
              allow: { to: { element: { type: ['features', 'shared'] } } },
            },
            {
              from: { element: { type: 'features' } },
              allow: { to: { element: { type: 'shared' } } },
            },
            {
              from: { element: { type: 'pages' } },
              disallow: { to: { element: { type: 'pages' } } },
              message: SLICE_MESSAGE,
            },
            {
              from: { element: { type: 'features' } },
              disallow: { to: { element: { type: 'features' } } },
              message: SLICE_MESSAGE,
            },
          ],
        },
      ],
    },
  }),
]
