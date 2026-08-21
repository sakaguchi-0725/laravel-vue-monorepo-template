---
name: impl-api
description: |
  apps/api（Laravel）のバックエンド実装スキル。

  以下の文脈で必ず使うこと:
  - 「〜APIを実装して」「〜エンドポイントを追加して」「〜ユースケースを作って」
  - 「バックエンドを実装して」「Laravel側を書いて」「apps/api に実装して」
  - コントローラー・UseCase・Eloquent モデル・マイグレーション・Feature テストを追加・修正するとき
  - apps/api/app/ 配下のファイルを新規作成・編集するとき
---

# impl-api スキル

`apps/api`（Laravel / PHP 8.4）の実装を、ドキュメント確認から完了報告まで一貫して行う。

## 前提

- **PHP・Composer・artisan・pint・phpstan・phpunit はすべて Docker コンテナ経由で実行する**。
  ローカルの PHP を使わない。実行は `apps/api` の mise タスク（`mise -C apps/api run <task>`）を通す
- 外部サービスの SDK・ライブラリを扱う場合は Context7 MCP で公式ドキュメントを確認する。
  記憶や推測でシグネチャを埋めない

---

## Step 1: 実装対象の確定

ユーザーから受け取った情報から以下を特定する。曖昧なまま進めず、この時点でまとめて質問する。

| 特定するもの | 例 |
| --- | --- |
| ドメイン | `article` |
| ユースケース | 記事を投稿する / 記事一覧を取得する |
| 呼び出し元 | `apps/web`（ユーザー向け）か `apps/admin`（管理者向け）か、両方か |
| エンドポイント | `POST /articles` |

OpenAPI 仕様書のパスが `/admin/` 始まりなら `Http/Admin/`、そうでなければ `Http/Web/` に置く。

---

## Step 2: ドキュメント確認

設計ドキュメントと OpenAPI 仕様書の**両方を必ず読む**。片方でも欠けている場合は実装しない。

### 設計ドキュメント

- `docs/design/{domain}/index.md` — ユースケース一覧・ドメイン概要
- `docs/design/{domain}/{use-case}.md` — 受け入れ基準（Given/When/Then）

### OpenAPI 仕様書

`docs/api/{web,admin}/openapi.yml` と `docs/api/{web,admin}/paths/{domain}.yml` を読み、
実装対象のエンドポイントについて以下を確定させる。

- パス・HTTP メソッド・パスパラメータ・クエリパラメータ
- リクエストボディのスキーマ（`required`・型・`minLength` などの制約）
- レスポンスのステータスコードとスキーマ
- 返しうるエラーレスポンス（`shared/error.yml` から `$ref` している `responses`）
- enum の backing 値（`pending` / `done` など）

実装は仕様書に合わせる。仕様書側が誤っていると判断した場合はユーザーに確認する。

### ドキュメントが無い場合

実装せず、不足しているものを報告してフローを中断する。

| 不足しているもの | 報告内容 |
| --- | --- |
| `docs/design/{domain}/` | 「設計ドキュメントが未整備のため実装できません。先に design-docs スキルで作成してください」 |
| `docs/api/` の該当エンドポイント | 「API 仕様書に定義がないため実装できません。先に api-spec スキルで作成してください」 |

---

## Step 3: 実装方針の検討

### コードベース調査

`apps/api` を調べ、既存の構成に合わせる。

- `app/Http/{Admin,Web}/` — 既存ドメインのディレクトリ構成・命名
- `app/UseCases/` — 既存 UseCase と Dto の書き方
- `app/Models/` — 既存モデル、スコープ・メソッドの定義位置
- `app/Externals/` — 外部サービスの interface と実装
- `app/Exceptions/` — 投げられる業務例外と `ErrorCode`
- `database/migrations/` — 実装対象のテーブルの DDL があるか
- `tests/Feature/` — 既存 Feature テストの構成
- `routes/api.php` — ルート定義のパターン

### ルールとサンプルコードを読む

方針を固める前に、以下を**必ず**読む。

| ファイル | 内容 |
| --- | --- |
| `.claude/rules/backend/architecture.md` | レイヤー構成と各層の責務（ルール） |
| `.claude/rules/backend/php.md` | PHP / Laravel コーディングルール |
| [`references/architecture.md`](./references/architecture.md) | 各層の OK / NG サンプルコード |
| [`references/coding.md`](./references/coding.md) | コーディングルールの OK / NG サンプルコード |
| [`references/testing.md`](./references/testing.md) | テストの方針とサンプルコード |
| [`references/report-format.md`](./references/report-format.md) | 実装計画・完了報告のフォーマット |

---

## Step 4: 実装計画の報告

作成・変更するファイルを列挙し、ユーザーに報告する。
フォーマットは [`references/report-format.md`](./references/report-format.md) の「実装計画フォーマット」に従う。

**ユーザーから OK が出るまで実装に着手しない。**

---

## Step 5: 実装・テスト

### 実装順序

以下の順に作成する。

1. `database/migrations/` — DDL が無ければ作成する（`mise -C apps/api run migrate:new <name>`）
2. `app/Models/` — Eloquent モデル。条件付き取得はスコープ／メソッドとして定義する
3. `app/Externals/{Service}/` — 外部サービスを使う場合のみ。interface・`Http〜Client`・Dto
4. `app/Providers/` — Externals の DI 登録（専用 ServiceProvider。`AppServiceProvider` に書かない）
5. `app/UseCases/{Domain}/Dto/` — Input / Output DTO（`readonly`）
6. `app/UseCases/{Domain}/{UseCase}UseCase.php` — 公開メソッドは `__invoke` のみ
7. `app/Http/{Admin,Web}/{Domain}/Requests/` — FormRequest
8. `app/Http/{Admin,Web}/{Domain}/Resources/` — Resource（Output DTO を受け取る）
9. `app/Http/{Admin,Web}/{Domain}/{Domain}Controller.php` — コントローラー
10. `routes/api.php` — ルート定義
11. `tests/Feature/` — Feature テスト

マイグレーションを追加した場合は適用する。

```bash
mise -C apps/api run migrate:up
```

### テスト

Step 2 で読んだ受け入れ基準（Given/When/Then）のシナリオを1つずつテストメソッドに落とす。
正常系・異常系を漏らさず拾い、書き終えたらシナリオ一覧と突き合わせて欠けがないか確認する。
書き方は [`references/testing.md`](./references/testing.md) に従う。

```bash
mise -C apps/api run test
```

失敗したテストは修正して再実行する。全て pass するまで繰り返す。

---

## Step 6: format と静的解析

```bash
mise -C apps/api run format
mise -C apps/api run lint
```

`format` は Pint がコードを書き換えるため `lint` より先に実行する。
エラーが残った場合は修正して両方を再実行する。エラー0件になるまで繰り返す。

PHPat（`tests/Architecture/`）の指摘は ignore や回避で通さず、設計を直す。

---

## Step 7: レビューエージェント

Agent ツールで `api-reviewer` を起動し、今回作成・変更したファイルの一覧を渡してレビューを依頼する。

CRITICAL / HIGH の指摘があれば修正し、再度 `api-reviewer` を起動する。
CRITICAL / HIGH が残らなくなるまで繰り返す。修正後は Step 6 をやり直す。

---

## Step 8: 完了報告

フォーマットは [`references/report-format.md`](./references/report-format.md) の「完了報告フォーマット」に従う。
