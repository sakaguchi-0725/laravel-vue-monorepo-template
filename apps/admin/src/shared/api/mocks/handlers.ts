import { http } from './http.ts'

export const handlers = [
  http.post('/admin/examples', async ({ request, response }) => {
    const body = await request.json()

    return response(200).json(body)
  }),
]
