# OpenAPI 仕様書のファイル構成と記載パターン

`docs/api/` 配下をどう分割し、各ファイルに何を書くか。

貫く原則: **`openapi.yml` の `paths` は参照のみ。定義は「web / admin 横断で使うか、そのアプリのドメイン固有か」で置き場所が決まる。**

## ファイル構成

```
docs/api/
├── web/                           # apps/web（ユーザー向け）が使う仕様書
│   ├── openapi.yml                # info / servers / tags と paths への $ref
│   └── paths/
│       └── {domain}.yml           # パス定義 + ドメイン固有スキーマ
├── admin/                         # apps/admin（管理者向け）が使う仕様書
│   ├── openapi.yml
│   └── paths/
│       └── {domain}.yml
├── shared/                        # web / admin 両方から参照する共通定義
│   └── error.yml
├── web.html                       # 生成されるHTMLドキュメント（gitignore済み）
└── admin.html
```

- 仕様書は web / admin で完全に独立している。片方の `paths/` からもう片方の `paths/` を参照しない
- 共通化するのは `shared/` に置いたものだけ

## openapi.yml — paths は参照のみ

`openapi.yml` に書くのは `info` / `servers` / `security` / `tags` と、`paths` からの `$ref` だけ。パス定義もスキーマも書かない。

```yaml
openapi: 3.1.0

info:
  title: App Web API
  version: 0.0.0
  description: ユーザー向けフロントエンドが利用するAPI。

servers:
  - url: http://localhost:8000
    description: ローカル開発環境

security: []

tags:
  - name: Account
    description: アカウント関連のエンドポイント。

paths:
  /accounts:
    $ref: './paths/account.yml#/collection'
  /accounts/{accountId}:
    $ref: './paths/account.yml#/resource'
```

- `servers` の URL に `/api` は含めない。Laravel 側のルートは `/api` prefix 配下だが、契約テスト（Spectator）が `SPECTATOR_PATH_PREFIX=api` で除去するため、仕様書のパスは prefix なしで書く
- `security: []` は認証が未導入のため空になっている
- `components` セクションは、その仕様書内で共有する定義が出るまで書かない（共通定義は `shared/`、ドメイン固有は `paths/` に置くため、通常は不要）

## paths/{domain}.yml — ドメイン単位で1ファイル

パス定義とそのドメインだけが使うスキーマを同じファイルに書く。ファイルは**ドメイン単位**で分割し、URLごとには分けない。

パスが複数ある場合は、ルートに `collection`（`/accounts`）/ `resource`（`/accounts/{accountId}`）などのキーを置き、`openapi.yml` から `#/collection` で参照する。パスが1つだけなら、ファイルルートをそのまま Path Item にしてよい（既存の `paths/example.yml` がこの形）。

ドメイン固有スキーマはファイルルートに定義し、`#/SchemaName` で参照する。YAML アンカー（`&Example` / `*Example`）は参照名がドキュメントに残らないため、複数箇所から使うスキーマには使わない。

```yaml
# web/paths/account.yml

# ── パス定義 ──
collection:
  get:
    operationId: ListAccounts
    summary: アカウント一覧を取得する
    tags:
      - Account
    responses:
      '200':
        description: 取得成功
        content:
          application/json:
            schema:
              $ref: '#/ListAccountsResponse'   # 同ファイル内のスキーマ
      '400':
        $ref: '../../shared/error.yml#/components/responses/BadRequest'  # 共通定義
  post:
    operationId: CreateAccount
    summary: アカウントを作成する
    tags:
      - Account
    requestBody:
      required: true
      content:
        application/json:
          schema:
            $ref: '#/CreateAccountRequest'
    responses:
      '201':
        description: 作成成功
        content:
          application/json:
            schema:
              $ref: '#/Account'                # GET 詳細と同一スキーマ
      '400':
        $ref: '../../shared/error.yml#/components/responses/BadRequest'

# ── ドメイン固有スキーマ ──
Account:
  type: object
  required:
    - id
    - displayName
  properties:
    id:
      type: integer
      examples:
        - 1
    displayName:
      type: string
      description: 表示名。
      examples:
        - 山田太郎

ListAccountsResponse:
  type: object
  required:
    - accounts
  properties:
    accounts:
      type: array
      items:
        $ref: '#/Account'
```

## shared/ — web / admin 横断の共通定義

`shared/` に置くのは **web / admin をまたいで3箇所以上から使う定義だけ**。それ以外は使う側の `paths/{domain}.yml` に定義する。2箇所で重複していても、3箇所目が出るまでは切り出さない。

ファイルは `components:` を持つ形で書き、`#/components/schemas/...` / `#/components/responses/...` で参照する（`shared/error.yml` がこの形）。`paths/` からの相対パスは `../../shared/{file}.yml` になる。

```yaml
# shared/error.yml
components:
  schemas:
    ErrorCode:
      type: string
      enum:
        - INVALID_ARGUMENTS

  responses:
    BadRequest:
      description: リクエストの内容が不正。
      content:
        application/json:
          schema:
            $ref: '#/components/schemas/Error'
```

新しいエラーコードを使う場合は `ErrorCode` の enum に追加し、対応する `responses` も同じファイルに追加する（詳細は [`api-design-rules.md`](./api-design-rules.md) 6）。

## OpenAPI 3.1 の記法

この仕様書は `openapi: 3.1.0`。3.0 系の記法をそのまま持ち込むと lint エラーになる。

| 用途 | 3.1 の書き方 | 使わない |
| --- | --- | --- |
| サンプル値 | `examples:` （配列） | `example:` |
| null 許容 | `type: [string, 'null']` | `nullable: true` |

## POST / PATCH / DELETE のレスポンス

POST・PATCH は作成・更新後のリソースを GET 詳細と同一スキーマで返す。DELETE のみ `204` でボディを持たない（理由は [`api-design-rules.md`](./api-design-rules.md) 5 を参照）:

```yaml
# web/paths/account.yml
resource:
  patch:
    operationId: UpdateAccount
    summary: アカウントを更新する
    tags:
      - Account
    requestBody:
      required: true
      content:
        application/json:
          schema:
            $ref: '#/UpdateAccountRequest'
    responses:
      '200':
        description: 更新成功
        content:
          application/json:
            schema:
              $ref: '#/Account'         # GET 詳細と同一スキーマ
      '400':
        $ref: '../../shared/error.yml#/components/responses/BadRequest'
  delete:
    operationId: DeleteAccount
    summary: アカウントを削除する
    tags:
      - Account
    responses:
      '204':
        description: 削除成功
      '400':
        $ref: '../../shared/error.yml#/components/responses/BadRequest'
```

PATCH のリクエストボディは全フィールドを `required` から外す（省略＝変更なし）。クリアを許可するフィールドにだけ `'null'` を型に含める:

```yaml
# web/paths/account.yml
UpdateAccountRequest:
  type: object
  properties:
    displayName:
      type: string                 # null 不可。省略すれば変更なし
    note:
      type: [string, 'null']       # null でクリア可能
      description: 備考。null を指定するとクリアされる。
    isActive:
      type: boolean                # false は「false に更新」であってクリアではない
  # required は書かない
```
