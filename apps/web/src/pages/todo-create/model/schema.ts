import * as z from 'zod'

export const schema = z.object({
  title: z.string().min(1).max(100),
  description: z.string(),
  dueOn: z.string(),
})

export type Form = z.infer<typeof schema>
