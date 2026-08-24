---
name: impl-front
description: |
  apps/web・apps/admin（Vue 3 + FSD）のフロントエンド実装スキル。

  以下の文脈で必ず使うこと:
  - 「画面を作って」「フロントを実装して」「〜ページを追加して」「Vue側を書いて」
  - 「フォームを実装して」「一覧画面を作って」など具体的なUI実装
  - 「apps/web に実装して」「apps/admin に実装して」
  - pages / features / shared（FSDレイヤー）にコンポーネント・composable・ルートを追加・修正するとき
  - Storybook の story や play 関数テストを書くとき
---

# impl-front スキル

`apps/web` / `apps/admin`（Vue 3 / Feature Sliced Design）の実装を、ドキュメント確認から完了報告まで一貫して行う。

## 前提

- **node / pnpm は mise タスク経由で実行する**（`mise -C apps/web run <task>`）。
  素の `pnpm` は古い実体を指し `devEngines` で失敗する。タスクに無いコマンドが必要なら
  `mise -C apps/web exec -- pnpm ...` の形にする
- 規約は `.claude/rules/frontend/`（`fsd.md` / `vue.md` / `typescript.md`）に定義され、
  対象ファイルの編集時に自動適用される。**本スキルでは規約を再掲しない**
- ライブラリのシグネチャは推測せず、Context7 MCP か `node_modules` の型定義で確認する

---

## Step 1: 実装対象の確定

ユーザーから受け取った情報から以下を特定する。曖昧なまま進めず、この時点でまとめて質問する。

| 特定するもの | 例 |
| --- | --- |
| アプリ | `apps/web`（ユーザー向け）か `apps/admin`（管理者向け）か、両方か |
| ドメイン | `todo` |
| 画面 | タスク一覧 / タスク作成 |
| 使用するエンドポイント | `GET /todos`（`ListTodos`） |

両アプリに同じ画面を作る場合も、参照する仕様書（`docs/api/web` / `docs/api/admin`）と
`src/shared/api/schema.ts` はアプリごとに別物として扱う。

---

## Step 2: ドキュメント確認

設計ドキュメントと OpenAPI 仕様書の**両方を必ず読む**。片方でも欠けている場合は実装しない。

### 設計ドキュメント

- `docs/design/{domain}/index.md` — ユースケース一覧・ドメイン概要
- `docs/design/{domain}/{use-case}.md` — 受け入れ基準（Given/When/Then）

受け入れ基準のシナリオが、そのまま Storybook の story になる。

### OpenAPI 仕様書

`docs/api/{web,admin}/openapi.yml` と `docs/api/{web,admin}/paths/{domain}.yml` を読み、
実装対象のエンドポイントについて以下を確定させる。

- パス・HTTP メソッド・パスパラメータ・クエリパラメータ
- リクエストボディのスキーマ（`required`・型・`minLength` などの制約）→ zod スキーマの根拠にする
- レスポンスのスキーマ（`null` を取りうるプロパティ・enum の値）
- 返しうるエラーレスポンス（`shared/error.yml` の `Error`。`code` と `message` を持つ）

**画面に出すエラー文言は自前で書かず、レスポンスの `message` を表示する**。
仕様書側が誤っていると判断した場合はユーザーに確認する。

### ドキュメントが無い場合

実装せず、不足しているものを報告してフローを中断する。

| 不足しているもの | 報告内容 |
| --- | --- |
| `docs/design/{domain}/` | 「設計ドキュメントが未整備のため実装できません。先に design-docs スキルで作成してください」 |
| `docs/api/` の該当エンドポイント | 「API 仕様書に定義がないため実装できません。先に api-spec スキルで作成してください」 |

---

## Step 3: 実装方針の検討

### コードベース調査

対象アプリを調べ、既存の構成に合わせる。

- `src/pages/` — 既存スライスのセグメント構成・命名
- `src/app/routes/` — ルート定義の分割とルート名の付け方
- `src/shared/api/` — `client` と `schema.ts`、`mocks/` のハンドラ
- `src/shared/test/` — 遷移検証のユーティリティ
- `src/shared/ui/` — アプリ内で使い回している汎用コンポーネント
- `packages/ui/src/` — 両アプリで共通化された汎用コンポーネント（`@repo/ui`）
- `.storybook/preview.ts` — story に効いているデコレータ・パラメータ

### ルールとサンプルコードを読む

方針を固める前に、以下を**必ず**読む。

| ファイル | 内容 |
| --- | --- |
| `.claude/rules/frontend/fsd.md` | レイヤー・スライス・セグメントの規約（配置の判断基準） |
| `.claude/rules/frontend/vue.md` | Vue コーディングルール |
| `.claude/rules/frontend/typescript.md` | TypeScript コーディングルール |
| [`references/guidelines.md`](./references/guidelines.md) | 実装の標準構成とサンプルコード一式 |
| [`references/test-viewpoints.md`](./references/test-viewpoints.md) | テスト観点のカタログ |
| [`references/report-format.md`](./references/report-format.md) | 実装計画・完了報告のフォーマット |

配置は `fsd.md` を読んで確定する。依存方向とスライス分離は ESLint（`boundaries`）で
検出されるため、後から弾かれて手戻りしないよう計画段階で決める。

---

## Step 4: 実装計画の報告

作成・変更するファイルをツリー形式で列挙し、ユーザーに報告する。
フォーマットは [`references/report-format.md`](./references/report-format.md) の「実装計画フォーマット」に従う。

判断を先送りしない。「実装時に検討」を計画に残さず、不明点はこの時点で質問して確定させる。

**ユーザーから OK が出るまで実装に着手しない。**

---

## Step 5: 実装・テスト

### 型生成

仕様書から型を生成する。

```bash
mise -C apps/web run api:gen
```

`src/shared/api/schema.ts` は生成物なので**手で編集しない**。API の型は
`@/shared/api` の `ApiSchema`（`components['schemas']` のエイリアス）から参照し、
型定義は `model/types.ts` に置く（`export type Todo = ApiSchema['Todo']`）。

### 実装順序

以下の順に作成する。詳細は [`references/guidelines.md`](./references/guidelines.md) に従う。

1. `src/pages/{画面名}/model/schema.ts` — 入力の zod スキーマ（フォームがある場合）
2. `src/pages/{画面名}/model/types.ts` — API の型エイリアス・描画用の型
3. `src/pages/{画面名}/model/use-{名前}.ts` — API 呼び出し・フォーム送信の composable
4. `src/pages/{画面名}/ui/{画面名}-page.vue` — 画面本体
5. `src/pages/{画面名}/index.ts` — public API
6. `src/app/routes/{domain}.ts` — ルート定義（`src/app/routes/index.ts` に追加）
7. `src/shared/api/mocks/handlers.ts` — 既定のモックが必要な場合のみ
8. `src/pages/{画面名}/ui/{画面名}-page.stories.ts` — story と play 関数

複数の画面で再利用するものだけ `src/features/` に置く。1画面でしか使わないものは
page スライス内に置く。ドメインに依存しない汎用コンポーネントは `src/shared/ui/`、
両アプリで共通化するものだけ `packages/ui`（`@repo/ui`）に置く。

### テスト

Step 2 で読んだ受け入れ基準のシナリオを story に落とす。観点は
[`references/test-viewpoints.md`](./references/test-viewpoints.md) のカタログから、その画面に該当するものを選ぶ。

```bash
mise -C apps/web run test
```

失敗した story は実装またはテストを修正して再実行する。全て pass するまで繰り返す。

---

## Step 6: format と静的解析・ビルド

```bash
mise -C apps/web run format
mise -C apps/web run lint
mise -C apps/web run build
```

`format` は Prettier がコードを書き換えるため先に実行する。
`lint` は FSD の依存方向・スライス分離の違反もここで検出する。**ignore や相対パス直参照で
回避せず、配置を直す**。`build` は `vue-tsc` の型チェックを含み、story では拾えない
型不整合を検出する。3つすべてエラー0件になるまで繰り返す。

両アプリを変更した場合は `apps/admin` でも同じ3つを実行する。

---

## Step 7: レビューエージェント

Agent ツールで `frontend-reviewer` を起動し、今回作成・変更したファイルの一覧を渡してレビューを依頼する。

CRITICAL / HIGH の指摘があれば修正し、再度 `frontend-reviewer` を起動する。
CRITICAL / HIGH が残らなくなるまで繰り返す。修正後は Step 5 のテストと Step 6 をやり直す。

---

## Step 8: 完了報告

フォーマットは [`references/report-format.md`](./references/report-format.md) の「完了報告フォーマット」に従う。

---

## mise タスク一覧

`-C apps/web` は対象アプリに応じて `-C apps/admin` に読み替える。

| タスク | 用途 |
| --- | --- |
| `mise -C apps/web run api:gen` | OpenAPI 仕様書から `src/shared/api/schema.ts` を生成 |
| `mise -C apps/web run test` | Storybook の play 関数テスト（vitest browser） |
| `mise -C apps/web run format` | Prettier で整形 |
| `mise -C apps/web run lint` | ESLint（FSD の依存方向チェックを含む） |
| `mise -C apps/web run build` | 型チェック＋本番ビルド |
| `mise -C apps/web run storybook` | Storybook を起動（目視確認用） |

---

## 品質チェック（報告前に確認）

- [ ] 設計ドキュメントと OpenAPI 仕様書の両方を読み、受け入れ基準を story に落とした
- [ ] `api:gen` を実行し、API の型を `@/shared/api` 経由で参照している（型を自前定義していない）
- [ ] `client` の `error` から `message` を表示している（エラー文言をハードコードしていない）
- [ ] スライス外からの import は `index.ts`（public API）経由になっている
- [ ] `mise -C apps/web run test` が全て pass している
- [ ] `format` / `lint` / `build` がエラー0件で通っている
- [ ] `frontend-reviewer` の CRITICAL / HIGH 指摘に対応済み
