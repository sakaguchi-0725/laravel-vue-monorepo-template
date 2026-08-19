import * as z from 'zod'

export const schema = z.object({
  name: z.string().min(1).max(50),
  email: z.email(),
})

export type Form = z.infer<typeof schema>
