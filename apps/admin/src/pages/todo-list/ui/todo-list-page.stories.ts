import type { Meta, StoryObj } from '@storybook/vue3-vite'
import { expect, userEvent, waitFor, within } from 'storybook/test'

import { http } from '@/shared/api/mocks'
import { expectRoute } from '@/shared/test'

import type { Todo } from '../model/types'

import TodoListPage from './todo-list-page.vue'

const todos: Todo[] = [
  {
    id: 2,
    title: '請求書を送付する',
    description: '今月分の請求書をPDFにして送る。',
    status: 'pending',
    dueOn: '2026-09-01',
  },
  {
    id: 1,
    title: '議事録をまとめる',
    description: null,
    status: 'done',
    dueOn: '2026-09-30',
  },
  {
    id: 3,
    title: '名刺を発注する',
    description: null,
    status: 'pending',
    dueOn: null,
  },
]

const meta = {
  title: 'pages/todo-list',
  component: TodoListPage,
  parameters: { initialRoute: { name: 'todo.list' } },
} satisfies Meta<typeof TodoListPage>

export default meta

type Story = StoryObj<typeof meta>

export const Default: Story = {
  name: 'タスクが取得した順に表示され、期限日と期限なしが表示されること',
  parameters: {
    msw: {
      handlers: [http.get('/admin/todos', ({ response }) => response(200).json({ todos }))],
    },
  },
  play: async ({ canvasElement }) => {
    const canvas = within(canvasElement)

    await waitFor(() => expect(canvas.getAllByRole('heading', { level: 2 })).toHaveLength(3))

    await expect(
      canvas.getAllByRole('heading', { level: 2 }).map((heading) => heading.textContent),
    ).toEqual(['請求書を送付する', '議事録をまとめる', '名刺を発注する'])

    await expect(canvas.getByText('今月分の請求書をPDFにして送る。')).toBeInTheDocument()
    await expect(canvas.getByText('期限 2026/09/01')).toBeInTheDocument()
    await expect(canvas.getByText('期限 2026/09/30')).toBeInTheDocument()
    await expect(canvas.getByText('期限なし')).toBeInTheDocument()
    const list = within(canvas.getByRole('list'))

    await expect(list.getAllByText('未完了')).toHaveLength(2)
    await expect(list.getByText('完了')).toBeInTheDocument()
  },
}

export const StatusFilter: Story = {
  name: 'ステータスに未完了を指定すると、未完了のタスクだけが表示されること',
  parameters: {
    msw: {
      handlers: [
        http.get('/admin/todos', ({ query, response }) => {
          const status = query.get('status')

          return response(200).json({
            todos: status === null ? todos : todos.filter((todo) => todo.status === status),
          })
        }),
      ],
    },
  },
  play: async ({ canvasElement }) => {
    const canvas = within(canvasElement)

    await waitFor(() => expect(canvas.getByText('議事録をまとめる')).toBeInTheDocument())

    await userEvent.selectOptions(canvas.getByLabelText('ステータス'), 'pending')

    await waitFor(() => expect(canvas.getAllByRole('heading', { level: 2 })).toHaveLength(2))

    await expect(canvas.getByText('請求書を送付する')).toBeInTheDocument()
    await expect(canvas.getByText('名刺を発注する')).toBeInTheDocument()
    await expect(canvas.queryByText('議事録をまとめる')).not.toBeInTheDocument()
  },
}

export const Empty: Story = {
  name: 'タスクが0件のとき空のメッセージが表示されること',
  parameters: {
    msw: {
      handlers: [http.get('/admin/todos', ({ response }) => response(200).json({ todos: [] }))],
    },
  },
  play: async ({ canvasElement }) => {
    const canvas = within(canvasElement)

    await waitFor(() => expect(canvas.getByText('タスクはまだありません。')).toBeInTheDocument())

    await expect(canvas.queryByRole('heading', { level: 2 })).not.toBeInTheDocument()
  },
}

export const FetchError: Story = {
  name: '取得が拒否されたとき、返却されたメッセージが表示されること',
  parameters: {
    msw: {
      handlers: [
        http.get('/admin/todos', ({ response }) =>
          response(400).json({
            code: 'INVALID_ARGUMENTS',
            message: 'ステータスの指定が正しくありません。',
          }),
        ),
      ],
    },
  },
  play: async ({ canvasElement }) => {
    const canvas = within(canvasElement)

    await waitFor(() =>
      expect(canvas.getByRole('alert')).toHaveTextContent('ステータスの指定が正しくありません。'),
    )

    await expect(canvas.queryByRole('heading', { level: 2 })).not.toBeInTheDocument()
  },
}

export const CreateLink: Story = {
  name: 'タスクを作成を押すと作成画面へ遷移すること',
  parameters: {
    msw: {
      handlers: [http.get('/admin/todos', ({ response }) => response(200).json({ todos }))],
    },
  },
  play: async ({ canvasElement }) => {
    const canvas = within(canvasElement)

    await userEvent.click(await canvas.findByRole('link', { name: 'タスクを作成' }))

    await expectRoute(canvasElement, '/todos/new')
  },
}
