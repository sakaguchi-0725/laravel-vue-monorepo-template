# 実装ガイドライン

Vue 3 + FSD + vue-router + vee-validate + zod + openapi-fetch + Storybook(play 関数) の標準的な書き方。
タスク一覧（取得系）とタスク作成（フォーム系）を例に、page スライス一式を示す。

規約（レイヤーの依存方向・Vue・TypeScript）は `.claude/rules/frontend/` に定義され、
編集時に自動適用される。ここでは再掲せず、規約に準拠したコード例のみを示す。

## 目次

- [スライス構成](#スライス構成)
- [ルーティング](#ルーティング)
- [API 呼び出し](#api-呼び出し)
- [取得系の composable](#取得系の-composable)
- [フォーム](#フォーム)
- [画面](#画面)
- [public API](#public-api)
- [Storybook](#storybook)

---

## スライス構成

1画面 = 1 page スライス。セグメントは `ui` / `model` のみを使い、`api` セグメントは作らない
（openapi-fetch の `client` を model から直接呼ぶため、リクエスト関数をラップする層を挟まない）。

```
src/pages/todo-list/
  ui/
    todo-list-page.vue           画面本体
    todo-list-page.stories.ts    story + play 関数
  model/
    use-todos.ts                 取得の composable
    types.ts                     APIの型エイリアス・描画用の型
  index.ts                       public API
```

- **画面は `ui/{画面名}-page.vue` に直接書く**。最初から子コンポーネントに細分化しない。
  肥大化してきたら（目安150行）意味のある単位で `ui/` 内の別ファイルに切り出す
- **型定義は `model/types.ts` に置く**（API の型エイリアス・描画用の型）。
  `Props` はコンポーネントと密結合なので例外
- 複数画面で再利用するものだけ `src/features/{domain}/` に置く
- ドメインに依存しない汎用コンポーネントは `src/shared/ui/` に置く。
  両アプリで共通化するものだけ `packages/ui`（`@repo/ui`）に置く

---

## ルーティング

ルート定義はドメインごとに `src/app/routes/{domain}.ts` へ切り出し、`src/app/routes/index.ts` の
`routes` に展開する。ルート名は `{domain}.{action}` 形式にする（story の `initialRoute` や
`RouterLink` の `:to="{ name: ... }"` から参照するため、パス直書きより名前を使う）。

```ts
// src/app/routes/todo.ts
import type { RouteRecordRaw } from 'vue-router'

import { TodoListPage } from '@/pages/todo-list'

export const todoRoutes: RouteRecordRaw[] = [
  {
    path: '/todos',
    name: 'todo.list',
    component: TodoListPage,
  },
]
```

```ts
// src/app/routes/index.ts
import { createMemoryHistory, createRouter, createWebHistory } from 'vue-router'

import { ExamplePage } from '@/pages/example'
import { todoRoutes } from './todo'

export const createAppRouter = (mode: 'web' | 'memory') => {
  const history = mode === 'web' ? createWebHistory() : createMemoryHistory()

  return createRouter({
    history,
    routes: [
      ...todoRoutes,
    ],
  })
}
```

`createAppRouter('memory')` は Storybook（`.storybook/preview.ts`）が使う。ルートを追加すると
story 側でも同じルーターが構築されるため、遷移先の画面も story から検証できる。

---

## API 呼び出し

`@/shared/api` の `client` を model の composable から直接呼ぶ。`client` は `schema.ts`（生成物）の
`paths` に拘束されているので、パス・パラメータ・ボディ・レスポンスはすべて型で守られる。

```ts
const { data, error } = await client.GET('/todos', { params: { query: { status: 'pending' } } })
const created = await client.POST('/todos', { body: { title } })
```

- `data` と `error` は排他。`error` は `docs/api/shared/error.yml` の `Error`（`code` / `message`）
- **例外は投げられない**。`try/catch` を書かず `error` を分岐する
- **画面に出す文言は `error.message` を使う**。ステータスごとの固定文言を自前で持たない
- API の型は `@/shared/api` の `ApiSchema`（`components['schemas']` のエイリアス）から
  参照する。同じ形の型を自前定義しない
- **型定義は `model/types.ts` に置く**。composable やコンポーネントの中で宣言しない

```ts
// src/pages/todo-list/model/types.ts
import type { ApiSchema } from '@/shared/api'

export type Todo = ApiSchema['Todo']
```

---

## 取得系の composable

読み込み状態・エラー・データを `ref` で持ち、画面から使う形にして返す。

```ts
// src/pages/todo-list/model/use-todos.ts
import { onMounted, ref } from 'vue'

import { client } from '@/shared/api'

import type { Todo } from './types'

export const useTodos = () => {
  const todos = ref<Todo[]>([])
  const errorMessage = ref<string>()
  const isLoading = ref(true)

  onMounted(async () => {
    const { data, error } = await client.GET('/todos')

    isLoading.value = false

    if (error) {
      errorMessage.value = error.message
      return
    }

    todos.value = data.todos
  })

  return { todos, errorMessage, isLoading }
}
```

派生値（表示用の整形・絞り込み）は composable 側で `computed` にして返す。テンプレートに
複雑な式を書かない。

---

## フォーム

入力スキーマは `model/schema.ts` に zod で定義する。制約は OpenAPI 仕様書の
`required` / `minLength` / `maxLength` などから写す。エラーメッセージは
`src/app/config/zod.ts`（`z.locales.ja()`）のグローバル設定に従うので、個別に指定しない。

```ts
// src/pages/todo-create/model/schema.ts
import * as z from 'zod'

export const schema = z.object({
  title: z.string().min(1).max(100),
})

export type Form = z.infer<typeof schema>
```

送信は vee-validate の `handleSubmit` の中で `client` を呼ぶ。`isSubmitting` は vee-validate が
管理するので自前のフラグを作らない。

```ts
// src/pages/todo-create/model/use-todo-form.ts
import { toTypedSchema } from '@vee-validate/zod'
import { useForm } from 'vee-validate'
import { ref } from 'vue'

import { client } from '@/shared/api'

import { schema, type Form } from './schema'

export const useTodoForm = (onCreated: () => void) => {
  const { defineField, errors, handleSubmit, isSubmitting } = useForm<Form>({
    validationSchema: toTypedSchema(schema),
  })

  const errorMessage = ref<string>()

  const onSubmit = handleSubmit(async (values) => {
    const { error } = await client.POST('/todos', { body: values })

    if (error) {
      errorMessage.value = error.message
      return
    }

    onCreated()
  })

  return { defineField, errors, onSubmit, isSubmitting, errorMessage }
}
```

送信成功後の遷移は画面側で `useRouter` を渡す形にする（composable がルーターに依存しない）。

---

## 画面

ロジックは composable に寄せ、SFC はテンプレートとスタイルに集中させる。

- ラベルと入力は `for` / `id` で紐づける（play 関数から `getByLabelText` で引けるようにする）
- エラー表示は `role="alert"` を付ける
- `<style scoped>` でスタイルをコンポーネントに閉じる

```vue
<!-- src/pages/todo-create/ui/todo-create-page.vue -->
<script setup lang="ts">
import { useRouter } from 'vue-router'

import { PrimaryButton } from '@repo/ui'

import { useTodoForm } from '../model/use-todo-form'

const router = useRouter()

const { defineField, errors, onSubmit, isSubmitting, errorMessage } = useTodoForm(() => {
  void router.push({ name: 'todo.list' })
})

const [title, titleAttrs] = defineField('title')
</script>

<template>
  <main class="page">
    <h1 class="title">タスクを作成</h1>

    <form class="form" novalidate @submit="onSubmit">
      <div class="field">
        <label class="label" for="title">件名</label>
        <input id="title" v-model="title" v-bind="titleAttrs" class="input" type="text" />
        <p v-if="errors.title" class="error" role="alert">{{ errors.title }}</p>
      </div>

      <p v-if="errorMessage" class="error" role="alert">{{ errorMessage }}</p>

      <PrimaryButton type="submit" :disabled="isSubmitting">作成</PrimaryButton>
    </form>
  </main>
</template>

<style scoped>
.page {
  max-width: 32rem;
  margin-inline: auto;
  padding: 2rem 1rem;
}

.error {
  font-size: 0.8125rem;
  color: #dc2626;
}
</style>
```

取得系の画面では、`isLoading` / `errorMessage` / 空状態 / 一覧の4分岐をテンプレートで出し分ける。
どの分岐も story から検証できるよう、状態ごとに固定の文言・要素を出す。

---

## public API

スライスの外から参照されるものだけを `index.ts` で公開する。スライス内部のファイルを
外から直接 import しない（ESLint の `boundaries` が検出する）。

```ts
// src/pages/todo-list/index.ts
export { default as TodoListPage } from './ui/todo-list-page.vue'
```

---

## Storybook

story は**画面単位**で書く。`title` は `pages/{スライス名}`。ルート名を持つ画面は
`parameters.initialRoute` に渡す（`.storybook/preview.ts` がこの値で `router.replace` する）。

API のモックは `@/shared/api/mocks` の `http`（openapi-msw）で書き、`parameters.msw.handlers`
に渡す。`response(200).json(...)` の型は仕様書に拘束されるため、レスポンス形状を間違えると
型エラーになる。全 story 共通のモックは `src/shared/api/mocks/handlers.ts` に置く。

```ts
// src/pages/todo-list/ui/todo-list-page.stories.ts
import type { Meta, StoryObj } from '@storybook/vue3-vite'
import { expect, waitFor, within } from 'storybook/test'

import { http } from '@/shared/api/mocks'

import TodoListPage from './todo-list-page.vue'

const meta = {
  title: 'pages/todo-list',
  component: TodoListPage,
  parameters: { initialRoute: { name: 'todo.list' } },
} satisfies Meta<typeof TodoListPage>

export default meta

type Story = StoryObj<typeof meta>

export const Default: Story = {
  name: 'タスクが一覧表示されること',
  parameters: {
    msw: {
      handlers: [
        http.get('/todos', ({ response }) =>
          response(200).json({
            todos: [
              {
                id: 1,
                title: '請求書を送付する',
                description: null,
                status: 'pending',
                dueOn: '2026-09-30',
              },
            ],
          }),
        ),
      ],
    },
  },
  play: async ({ canvasElement }) => {
    const canvas = within(canvasElement)

    await waitFor(() => expect(canvas.getByText('請求書を送付する')).toBeInTheDocument())
  },
}
```

- 検証する観点は [`test-viewpoints.md`](./test-viewpoints.md) から選ぶ
- 要素はアクセシブルなクエリ（`getByRole` / `getByLabelText`）で引く。CSS クラスや
  `data-testid` に依存しない
- 非同期の表示は `waitFor` / `findBy*` で待つ
- 送信内容の検証は、ハンドラ内で `await request.json()` した値をスパイ（`fn()`）に渡して
  `toHaveBeenCalledWith` で確認する
- 遷移の検証は `@/shared/test` の `expectRoute(canvasElement, path)` を使う
- 仕様書に定義が無いステータス（500 など）を返す場合のみ `response.untyped(...)` を使う
