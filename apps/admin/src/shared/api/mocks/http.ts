import { createOpenApiHttp } from 'openapi-msw'

import type { paths } from '../schema.ts'

export const http = createOpenApiHttp<paths>({ baseUrl: '*' })
