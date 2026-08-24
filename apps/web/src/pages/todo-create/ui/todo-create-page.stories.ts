import type { Meta, StoryObj } from '@storybook/vue3-vite'
import { expect, fn, userEvent, waitFor, within } from 'storybook/test'

import { http } from '@/shared/api/mocks'
import { expectRoute } from '@/shared/test'

import TodoCreatePage from './todo-create-page.vue'

const onCreate = fn()

const meta = {
  title: 'pages/todo-create',
  component: TodoCreatePage,
  parameters: { initialRoute: { name: 'todo.create' } },
  beforeEach: () => {
    onCreate.mockClear()
  },
} satisfies Meta<typeof TodoCreatePage>

export default meta

type Story = StoryObj<typeof meta>

const createdTodo = {
  id: 1,
  title: '請求書を送付する',
  description: '今月分の請求書をPDFにして送る。',
  status: 'pending',
  dueOn: '2026-09-30',
} as const

export const Success: Story = {
  name: '入力した内容で作成され、一覧へ遷移すること',
  parameters: {
    msw: {
      handlers: [
        http.post('/todos', async ({ request, response }) => {
          onCreate(await request.json())

          return response(201).json(createdTodo)
        }),
      ],
    },
  },
  play: async ({ canvasElement }) => {
    const canvas = within(canvasElement)

    await userEvent.type(await canvas.findByLabelText('件名'), '請求書を送付する')
    await userEvent.type(canvas.getByLabelText('詳細説明'), '今月分の請求書をPDFにして送る。')
    await userEvent.type(canvas.getByLabelText('期限日'), '2026-09-30')
    await userEvent.click(canvas.getByRole('button', { name: '作成' }))

    await waitFor(() =>
      expect(onCreate).toHaveBeenCalledWith({
        title: '請求書を送付する',
        description: '今月分の請求書をPDFにして送る。',
        dueOn: '2026-09-30',
      }),
    )

    await expectRoute(canvasElement, '/todos')
  },
}

export const TitleOnly: Story = {
  name: '件名だけを入力したとき、詳細説明と期限日を含まないリクエストが送られること',
  parameters: {
    msw: {
      handlers: [
        http.post('/todos', async ({ request, response }) => {
          onCreate(await request.json())

          return response(201).json({ ...createdTodo, description: null, dueOn: null })
        }),
      ],
    },
  },
  play: async ({ canvasElement }) => {
    const canvas = within(canvasElement)

    await userEvent.type(await canvas.findByLabelText('件名'), '請求書を送付する')
    await userEvent.click(canvas.getByRole('button', { name: '作成' }))

    await waitFor(() => expect(onCreate).toHaveBeenCalledWith({ title: '請求書を送付する' }))

    await expectRoute(canvasElement, '/todos')
  },
}

export const ValidationError: Story = {
  name: '件名が未入力のときエラーが表示され、送信されないこと',
  parameters: {
    msw: {
      handlers: [
        http.post('/todos', async ({ request, response }) => {
          onCreate(await request.json())

          return response(201).json(createdTodo)
        }),
      ],
    },
  },
  play: async ({ canvasElement }) => {
    const canvas = within(canvasElement)

    await userEvent.click(await canvas.findByRole('button', { name: '作成' }))

    await waitFor(() => expect(canvas.getByRole('alert')).toBeInTheDocument())

    await expect(onCreate).not.toHaveBeenCalled()
    await expectRoute(canvasElement, '/todos/new')
  },
}

export const CreateError: Story = {
  name: '作成が拒否されたとき、返却されたメッセージが表示されること',
  parameters: {
    msw: {
      handlers: [
        http.post('/todos', ({ response }) =>
          response(400).json({
            code: 'INVALID_ARGUMENTS',
            message: '期限日に過去の日付は指定できません。',
          }),
        ),
      ],
    },
  },
  play: async ({ canvasElement }) => {
    const canvas = within(canvasElement)

    await userEvent.type(await canvas.findByLabelText('件名'), '請求書を送付する')
    await userEvent.type(canvas.getByLabelText('期限日'), '2026-08-20')
    await userEvent.click(canvas.getByRole('button', { name: '作成' }))

    await waitFor(() =>
      expect(canvas.getByRole('alert')).toHaveTextContent('期限日に過去の日付は指定できません。'),
    )

    await expectRoute(canvasElement, '/todos/new')
  },
}
