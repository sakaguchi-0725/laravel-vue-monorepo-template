---
name: api-spec
description: |
  OpenAPI仕様書を作成・更新するスキル。
  docs/design/ の設計ドキュメントをもとにOpenAPIスペックを docs/api/{web,admin}/ に生成し、
  redoclyでlintと静的HTMLドキュメント生成を自動実行する。生成したHTMLはopenコマンドで即座に確認できる。

  以下の文脈で必ず使うこと:
  - 「API仕様書を作って」「OpenAPIを書いて」「APIスペックを追加して」
  - 「エンドポイントを追加・更新して」「APIドキュメントを書いて・直して」
  - 「openapi.ymlを作成・更新して」「redoclyでドキュメントを生成して」
  - docs/api/ 以下のファイルを新規作成・編集するとき
  - 設計ドキュメント（docs/design/）をAPI仕様書に落とし込みたいとき
---

# api-spec スキル

設計ドキュメントを読んでOpenAPI仕様書を書き、lintとHTML生成まで一気通貫で完成させるスキル。

## ゴール

- `docs/design/{domain}/` の設計ドキュメントを `docs/api/` のOpenAPI仕様書に落とし込む
- 設計規約と Redocly lint ルールの両方を満たす
- lint 通過後、HTMLドキュメントを生成してブラウザで確認できる状態にする

## Step 1: 設計ドキュメントを読む

対象ドメインの設計ドキュメントを読む:

- `docs/design/{domain}/index.md` — テーブル構成とユースケース一覧
- `docs/design/{domain}/{use-case}.md` — 各ユースケースの受け入れ基準

設計ドキュメントが存在しない場合はユーザーに確認する。

## Step 2: 対象の仕様書を決める

仕様書はフロントエンドのアプリ単位で2本に分かれている。エンドポイントの利用者がどちらかで置き場所が決まる。

| 仕様書 | 利用者 | パス |
| --- | --- | --- |
| `docs/api/web/openapi.yml` | `apps/web`（ユーザー向け） | `/{collection}` |
| `docs/api/admin/openapi.yml` | `apps/admin`（管理者向け） | `/admin/{collection}` |

どちらから呼ぶか設計ドキュメントで判別できない場合はユーザーに確認する。両方から呼ぶエンドポイントは、原則として両方の仕様書にそれぞれ定義する（レスポンスの粒度も権限も異なるため）。

## Step 3: OpenAPI仕様書を書く

**執筆前に以下の2ファイルを必ず読み、全項目に従う。**

| ファイル | 内容 |
| --- | --- |
| [`references/spec-structure.md`](./references/spec-structure.md) | ファイル構成と、`openapi.yml` / `paths/` / `shared/` の記載パターン |
| [`references/api-design-rules.md`](./references/api-design-rules.md) | URL設計・フィールド命名・operationId命名・HTTPメソッド・レスポンス設計・標準エラーコード |

作成・更新するファイル:

- `docs/api/{app}/openapi.yml` — `info` / `servers` / `tags` と、`paths` からの `$ref` のみ
- `docs/api/{app}/paths/{domain}.yml` — パス定義とドメイン固有スキーマ
- `docs/api/shared/*.yml` — web / admin 両方から使う共通定義（3箇所以上で使うものが出た場合のみ）

## Step 4: lintを実行して修正する

```bash
mise run api:lint
```

web / admin 両方をまとめて lint する。エラーが出た場合は修正してから次へ進む。

## Step 5: HTMLドキュメントを生成する

```bash
mise run api:docs
```

`docs/api/web.html` と `docs/api/admin.html` が生成され、ブラウザで自動的に開く（`open` は mise タスク内に含まれているため、別途実行しない）。

## 品質チェック（完了前に確認）

- [ ] [`references/spec-structure.md`](./references/spec-structure.md) に従っている
  - [ ] `openapi.yml` の `paths` は `$ref` のみ（パス定義もスキーマも書いていない）
  - [ ] ドメイン固有スキーマは `paths/{domain}.yml` のルートに定義し `#/SchemaName` で参照している
  - [ ] `shared/` にあるのは web / admin 横断で3箇所以上使うもののみ
  - [ ] OpenAPI 3.1 の記法になっている（`examples:` は配列、null許容は `type: [string, 'null']`、`nullable:` は使わない）
- [ ] [`references/api-design-rules.md`](./references/api-design-rules.md) に従っている
  - [ ] フィールド名が `camelCase` になっている
  - [ ] パスに `/api` prefix を付けていない（Laravel 側の prefix は Spectator が除去する）
  - [ ] admin 仕様書のパスが `/admin/` 始まりになっている
  - [ ] 部分更新に `PATCH` を使っている（`PUT` ではなく）
  - [ ] `operationId` が `動詞 + リソース名（UpperCamelCase）` になっている
  - [ ] POSTは `201` + リソース、PATCHは `200` + リソース、DELETEは `204`（ボディなし）
  - [ ] 新しいエラーコードを使う場合、`shared/error.yml` の `ErrorCode` enum と対応する `responses` を追加した
- [ ] lintが通っている（エラー0件。`operationId` 有無・4xx レスポンス・未使用コンポーネント等は Redocly が検出する）
- [ ] `docs/api/web.html` / `docs/api/admin.html` をブラウザで確認した
