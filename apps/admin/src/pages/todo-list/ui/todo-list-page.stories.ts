import type { Meta, StoryObj } from '@storybook/vue3-vite'
import { userEvent, within } from 'storybook/test'

import { expectRoute } from '@/shared/test'

import TodoListPage from './todo-list-page.vue'

const meta = {
  title: 'pages/todo-list',
  component: TodoListPage,
  parameters: { initialRoute: { name: 'todo.list' } },
} satisfies Meta<typeof TodoListPage>

export default meta

type Story = StoryObj<typeof meta>

export const Default: Story = {
  name: 'タスク一覧が表示されること',
}

export const NavigateToExample: Story = {
  name: 'リンクをクリックすると example に遷移すること',
  play: async ({ canvasElement }) => {
    const canvas = within(canvasElement)

    await userEvent.click(await canvas.findByRole('link', { name: 'サンプルフォームへ' }))

    await expectRoute(canvasElement, '/')
  },
}
