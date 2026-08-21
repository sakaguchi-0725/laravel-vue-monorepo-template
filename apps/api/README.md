# apps/api

Laravel によるバックエンド API。`web` / `admin` の2つのフロントエンドに対して JSON API を提供する。

## 前提

**PHP・Composer・artisan はすべて Docker コンテナ経由で実行すること** 

## セットアップ

`apps/api` で以下を実行する。

```
mise run setup
```

## 技術スタック

| 区分 | 使用しているもの | 補足 |
| --- | --- | --- |
| 言語 | PHP 8.4 | |
| フレームワーク | Laravel 13 | |
| 認証 | TBD | |
| DB | MySQL 8.4 | 開発用 `db` / テスト用 `test-db` を分離 |
| フォーマッタ | Pint | `laravel` preset + strict 系ルール（`pint.json`） |
| 静的解析 | PHPStan (level max) / Larastan / phpstan-strict-rules | `phpstan.neon` |
| アーキテクチャ検査 | PHPat | `tests/Architecture/` に定義。PHPStan の実行に含まれる |
| テスト | PHPUnit 12 / paratest | 並列実行前提 |
| 契約テスト | Spectator | 実レスポンスを `docs/api` の OpenAPI 定義と突き合わせる |

## ディレクトリ構成

`app/` 配下はレイヤーで分割し、依存方向は `Http/` → `UseCases/` → `Models/` / `Externals/` の一方向のみ。

- `Http/`: HTTP の入出力。リクエストの受け取り、認証、レスポンスの整形
- `UseCases/`: ユースケース1つ分の処理。業務ロジック、認可、トランザクション境界
- `Models/`: Eloquent モデル。永続化とデータ取得
- `Externals/`: 外部サービス連携
- `Exceptions/`: 業務例外。エラーの種別だけを持ち、HTTP ステータスは知らない
- `Providers/`: サービスコンテナへの登録

```
apps/api/app/
  Http/
    Admin/
      DomainA/
        TaskController.php
        Requests/
          StoreTaskRequest.php
        Resources/
          TaskResource.php
    Web/
      DomainA/
        TaskController.php
        Requests/
        Resources/
    Errors/
      ErrorResponseFactory.php
  UseCases/
    DomainA/
      Dto/
        CreateTaskInput.php
        TaskOutput.php
      CreateTaskUseCase.php
      ListTasksUseCase.php
  Models/
    Task.php
    User.php
  Exceptions/
    ErrorCode.php
    ApplicationException.php
    NotFoundException.php
  Externals/
    ServiceA/
      Dto/
      ServiceAClient.php
      HttpServiceAClient.php
  Providers/
    ExternalServiceProvider.php
```

## mise タスク一覧

| タスク | 内容 |
| --- | --- |
| `mise run setup` | 初期セットアップ（`.env` 作成、マイグレーション）。`docker:up` を先に実行する |
| `mise run migrate:new <name>` | マイグレーションファイルの作成 |
| `mise run migrate:up` | マイグレーションの適用 |
| `mise run migrate:down` | マイグレーションを1つ戻す |
| `mise run test` | テストの実行（PHPUnit / paratest による並列実行） |
| `mise run ide:helper` | エディタ補完用ヘルパーの生成（Laravel のマジックメソッド解決） |
| `mise run format` | PHP コードの整形（Pint） |
| `mise run format:check` | 整形チェック（Pint、書き換えなし） |
| `mise run lint` | 静的解析（PHPStan / Larastan / PHPat） |
