import { expect, waitFor, within } from 'storybook/test'

export const expectRoute = (canvasElement: HTMLElement, path: string) =>
  waitFor(() => expect(within(canvasElement).getByLabelText('route').textContent).toBe(path))
