# API 設計ルール

## 1. URL 設計

- リソース指向で設計する: `/{collection}/{resourceId}` の階層構造
- コレクション名は **複数形・camelCase**: `accounts`, `userEvents`
- **全パスの先頭セグメントは、ドメイン名の複数形コレクションを必ず付ける**
  - 機能名・動詞・bare な識別子だけのパスにしない（`/me`・`/profile`・`/login` は不可）
  - current-user / self リソースも例外なく複数形コレクションを付ける: `/{collection}/me`（bare `/me` は不可）
- パスパラメータ名は **リソース単数形 + `Id`**: `/accounts/{accountId}/sessions/{sessionId}`
  - `{id}` は使わない（ネスト時に名前が衝突し、OpenAPI のパラメータ名一意制約に違反する）
- バージョン prefix（`/v1`）は付与しない
- `/api` prefix は書かない。Laravel 側のルートは `/api` 配下だが、契約テスト（Spectator）が `SPECTATOR_PATH_PREFIX=api` で除去する
- admin 仕様書（`docs/api/admin/`）のパスは `/admin/` を先頭に付け、その次を複数形コレクションにする: `/admin/accounts/{accountId}`
  - web 仕様書（`docs/api/web/`）は `/admin/` を付けない: `/accounts/{accountId}`
- カスタム操作（CRUD標準メソッドで表現できない状態変更）はサブパスに動詞を置く形式: `POST /accounts/{accountId}/deactivate`

## 2. フィールド命名

| 種別 | 形式 | 例 |
|------|------|----|
| フィールド名 | `camelCase` | `displayName`, `isActive` |
| IDフィールド | `id`（integer） | `123` |
| 時刻フィールド | `string / format: date-time` (RFC 3339)。ドメイン上意味のある日時に付ける（`publishedAt`, `deactivatedAt` 等） | `"2024-01-01T00:00:00Z"` |

## 3. HTTP メソッド

| 操作 | メソッド | URLパターン |
|------|---------|------------|
| 一覧取得 | GET | `/accounts` |
| 単件取得 | GET | `/accounts/{accountId}` |
| 新規作成 | POST | `/accounts` |
| 部分更新 | **PATCH**（PUTは使わない） | `/accounts/{accountId}` |
| 削除 | DELETE | `/accounts/{accountId}` |
| カスタム操作 | POST | `/accounts/{accountId}/deactivate` |

## 4. operationId 命名

- 全エンドポイントに付ける
- 形式は **動詞 + リソース名（UpperCamelCase）**
  - Redocly は存在チェックのみで命名パターンは検証しないため、規約として明示する

| メソッド + パス | operationId |
|---|---|
| GET `/accounts` | `ListAccounts` |
| GET `/accounts/{accountId}` | `GetAccount` |
| POST `/accounts` | `CreateAccount` |
| PATCH `/accounts/{accountId}` | `UpdateAccount` |
| DELETE `/accounts/{accountId}` | `DeleteAccount` |
| POST `/accounts/{accountId}/deactivate` | `DeactivateAccount` |

## 5. レスポンス設計

### GET

- 一覧は `{ accounts: [...] }` 形式（コレクション名のキーで配列を返す）
- 一覧スキーマと詳細スキーマを分ける
  - 一覧（`GET /accounts`）は表示に必要な最小フィールドのみ（`name`, `avatarUrl`, `role` 等）
  - 単件（`GET /accounts/{accountId}`）は詳細＝全フィールド

### POST（作成）

- `201 Created` + 作成されたリソースを **詳細スキーマ**で返す（GET 単件と同一）
  - 書き込み経路が必要としないデータを、レスポンスのために追加クエリで展開しない
- 採番された `id` はクライアントが知り得ないため、ボディで返す

### PATCH（部分更新）

- `200 OK` + 更新後のリソースを **詳細スキーマ**で返す（GET 単件と同一）
  - 書き込み経路が必要としないデータを、レスポンスのために追加クエリで展開しない
- ボディに現れたフィールドのみ更新する（JSON Merge Patch / RFC 7386 と同じセマンティクス。`Content-Type` は `application/json` のまま）
  - 省略したフィールドは変更なし
  - `null` を明示したフィールドはクリア
- クリアを許可するフィールドは OpenAPI で型に `'null'` を含め（`type: [string, 'null']`。3.1 なので `nullable: true` は使わない）、`required` から外す
  - 型に `'null'` が無いフィールドに `null` が来た場合は 400 を返す

### DELETE（削除）

- `204 No Content`。ボディを返さない（`content` を書かない）

### カスタム操作（`/action`）

- 対象リソースの状態が変わる場合は `200 OK` + 変更後のリソース（詳細スキーマと同一）
- 副作用のみで返す状態がない場合は `204 No Content`

### リソース参照（別ドメインのデータ）

- 別集約のリソースは **ID（参照）だけ返す**。実体を埋め込まない（AIP-122）
  - 理由: 実体の埋め込みは権限を迂回し、リソースを密結合させる
  - 実体が必要なら該当リソースの取得APIを呼ぶ
- 例外1: 一覧で N+1 を避けたい場合、表示に必要な非正規化フィールド（`assigneeName` 等）を数個だけ持たせる
  - 参照先リソース全体は埋め込まない
- 例外2: 同一集約の子（親なしに存在しないデータ）は埋め込んでよい
  - これは参照ではない

### 権限によるフィールドの出し分け

- 同じリソースなら **エンドポイントは分けない**（`GET /accounts/{accountId}` は1つ）
- 権限で見せる・隠すが変わるフィールドは、同一スキーマ内で optional（`required` に入れない）にし、レスポンス生成側で権限に応じて絞る
- 権限差がフィールド数個に収まらず構造ごと異なる場合のみ、別リソース化を検討する（最後の手段）

### 共通

- 技術的なタイムスタンプ（`createdAt` / `updatedAt` 等）は、原則として詳細スキーマに含めない
  - 表示要件がある場合（作成日を出す等）は含めてよい
  - ただし日時がドメイン上の意味を持つ場合は、専用フィールド（`publishedAt` 等）として持つほうを優先する
  - 詳細スキーマを返す全メソッド（GET 単件 / POST / PATCH / カスタム操作）に適用する
- エラーは `{ code, message }` 形式（ネストしない。`code` はHTTPステータス外の独自コードにも使うため保持）

## 6. 標準エラーコード

| HTTP | 用途 |
|------|------|
| 400 | 不正なパラメータ |
| 401 | 認証なし |
| 403 | 権限なし |
| 404 | リソース未存在 |
| 409 | 重複・状態競合 |
| 429 | レート制限超過 |

レスポンスボディの `code`（`INVALID_ARGUMENTS` 等）とレスポンス定義は `docs/api/shared/error.yml` に集約する。

- 現状ここにあるのは `400` / `INVALID_ARGUMENTS` のみ。他のステータスを使う場合は `ErrorCode` の enum と `responses` を追加してから参照する

## 7. description の記法

人間が見るのは生成される HTML プレビュー（ReDoc）だけ。description は Markdown としてレンダリングされ、**単一改行は空白に潰れて改行されない**。

- 2文以上になる場合はブロックスカラー（`|`）を使い、`。` の直後に**空行**を入れて段落を分ける
  - 末尾の半角スペース2つによる改行（Markdown のハードブレイク）は使わない。エディタや整形ツールが行末の空白を自動的に削除し、無言で改行が消えることがあるため
- ファイル上で改行するだけ（空行なし）では、プレビューで1行に繋がってしまう

```yaml
# 良い例（プレビューで段落が分かれる）
description: |
  氏名。

  本登録時にアカウント情報へ反映する。

# 悪い例（ファイル上は改行されているが、プレビューでは1行に繋がる）
description: |
  氏名。
  本登録時にアカウント情報へ反映する。
```

## 8. 外部サービス連携時の検証

- 認証・決済など外部サービス（Cognito 等）の仕様に依存する API は、**公式ドキュメント（AWS SDK/API リファレンス）を確認してからスキーマを書く**
  - 例: Cognito の `InitiateAuth` は追加チャレンジ時に `Session` を返し、`RespondToAuthChallenge` はその `Session` を必須で受け取る。ログイン開始のレスポンスに `session` を含め、コード検証のリクエストで受け取ること
  - 推測でフィールドを省略しない（フロントが後続処理に必要とするトークン・セッションを落とさない）
