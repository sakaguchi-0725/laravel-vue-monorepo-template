import type { Meta, StoryObj } from '@storybook/vue3-vite'
import { expect, userEvent, waitFor, within } from 'storybook/test'

import ExamplePage from './example-page.vue'

const meta = {
  title: 'pages/example',
  component: ExamplePage,
} satisfies Meta<typeof ExamplePage>

export default meta

type Story = StoryObj<typeof meta>

export const Success: Story = {
  name: '入力した値が送信結果として表示されること',
  play: async ({ canvasElement }) => {
    const canvas = within(canvasElement)

    await userEvent.type(await canvas.findByLabelText('名前'), '山田太郎')
    await userEvent.type(canvas.getByLabelText('メールアドレス'), 'taro@example.com')
    await userEvent.click(canvas.getByRole('button', { name: '送信' }))

    await waitFor(() =>
      expect(canvas.getByText('送信しました: 山田太郎（taro@example.com）')).toBeInTheDocument(),
    )
  },
}

export const ValidationError: Story = {
  name: '不正な入力でエラーが表示され、送信結果が出ないこと',
  play: async ({ canvasElement }) => {
    const canvas = within(canvasElement)

    await userEvent.click(await canvas.findByRole('button', { name: '送信' }))

    await waitFor(() => expect(canvas.getAllByText(/無効な入力/)).toHaveLength(2))

    await userEvent.type(canvas.getByLabelText('名前'), '山田太郎')
    await userEvent.type(canvas.getByLabelText('メールアドレス'), 'invalid-email')
    await userEvent.click(canvas.getByRole('button', { name: '送信' }))

    await waitFor(() => expect(canvas.getByText('無効なメールアドレス')).toBeInTheDocument())
    await expect(canvas.queryByText(/送信しました/)).not.toBeInTheDocument()
  },
}
