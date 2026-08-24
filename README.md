# laravel-vue-monorepo-template

Laravel（API）+ Vue 3（web / admin）のモノレポテンプレート。

## 技術スタック

| 領域                 | 技術                              |
| -------------------- | --------------------------------- |
| モノレポ             | pnpm workspace / mise             |
| apps/api             | PHP 8.4 / Laravel 13 / MySQL 8.4  |
| apps/web, apps/admin | TypeScript / Vue 3 / Vite         |
| API 仕様             | OpenAPI 3                         |
| 実行環境             | Docker Compose                    |

各アプリの詳細（構成・ツール・テスト）はアプリごとの README を参照。

## セットアップ

前提: [mise](https://mise.jdx.dev/) と Docker がインストール済みであること。

```sh
# ツール（Node / pnpm / lefthook）の取得
mise install

# 依存インストール、git hooks 登録、各アプリのセットアップ（コンテナ起動・マイグレーションを含む）
mise run setup
```

起動:

```sh
# 全アプリの開発サーバーを一括起動
mise run dev
```

| アプリ | URL                   |
| ------ | --------------------- |
| api    | http://localhost:8000 |
| web    | http://localhost:5173 |
| admin  | http://localhost:5174 |

個別に起動する場合は `mise -C apps/<app> run dev`。各アプリ固有のタスクは `mise -C apps/<app> tasks` で確認する。

## ディレクトリ構成

```
.
├── apps
│   ├── api       Laravel（REST API）
│   ├── web       Vue 3（エンドユーザー向け）
│   └── admin     Vue 3（管理画面）
├── packages
│   ├── ui             web / admin 共通の Vue コンポーネント（@repo/ui）
│   └── eslint-config  共通 ESLint 設定（@repo/eslint-config）
├── docs
│   ├── adr       アーキテクチャ決定記録
│   ├── api       OpenAPI 仕様書（web / admin / shared）
│   └── design    設計ドキュメント（ドメインごとのユースケース・受け入れ基準）
├── .claude       Claude Code の設定（skills / agents / rules）
├── compose.yml   Docker Compose 定義
├── lefthook.yml  pre-commit の定義
└── .mise.toml    ツールバージョンと root タスク
```

## root の mise タスク

| タスク           | 内容                                                       |
| ---------------- | ---------------------------------------------------------- |
| `setup`          | 依存のインストール、git hooks の登録、各アプリのセットアップ |
| `dev`            | 全アプリの開発サーバーを起動（各アプリの `dev` を並列実行）  |
| `docker:up`      | コンテナの起動                                             |
| `docker:down`    | コンテナの停止                                             |
| `docker:log`     | api コンテナのログ表示                                     |
| `api:lint`       | OpenAPI 仕様書の lint                                      |
| `api:docs`       | OpenAPI 仕様書の HTML ドキュメント生成                     |
| `format:staged`  | 指定ファイルを Prettier で整形する（lefthook 用）          |
