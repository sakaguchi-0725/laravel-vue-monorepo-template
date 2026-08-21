---
paths:
  - "apps/api/app/**/*.php"
---

# バックエンドアーキテクチャ

## レイヤー

`apps/api/app/` 配下は以下のレイヤーで構成する。

- `Http/`: HTTP の入出力。リクエストの受け取り、認証、レスポンスの整形
- `UseCases/`: ユースケース1つ分の処理。業務ロジック、認可、トランザクション境界
- `Models/`: Eloquent モデル。永続化とデータ取得
- `Externals/`: 外部サービス連携
- `Exceptions/`: 業務例外。エラーの種別だけを持つ
- `Providers/`: サービスコンテナへの登録

依存方向は `Http/` → `UseCases/` → `Models/` / `Externals/` の一方向のみ。逆方向の参照を作らない。
`Exceptions/` はこの並びの外側にあり、どのレイヤーからでも参照してよい。代わりに `Exceptions/` から
他のレイヤーを参照しない。

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

## Http 層

- `Http/` の直下は Admin / Web で分け、その下をドメインで分ける。ただし `Http/Errors/` は
  Admin / Web のどちらにも属さない共通部品として例外的に直下に置く
- コントローラーに基底クラスを作らない。ドメインディレクトリ直下に置くのはコントローラーのみで、
  他のクラスを置かない
- コントローラーが使う FormRequest と Resource は、そのコントローラーと同じドメインディレクトリの `Requests/` `Resources/` に置く。`Http/Requests/` `Http/Resources/` のような技術的関心を最上位に置いたディレクトリは作らない
- Admin と Web で Request / Resource を共有しない。同じ形になっていても、公開する項目とバリデーションは画面ごとに変わるため、それぞれの配下に定義する
- コントローラーの責務は「リクエストを UseCase の Input DTO に変換する」「UseCase を呼ぶ」「戻ってきた Output DTO を Resource に渡す」の3つのみ。それ以外の処理を書かない
- 認証は Http 層で完結させる。ミドルウェア（`auth:sanctum`）で表現し、UseCase に持ち込まない
- 認可は Http 層に書かない。認証済みユーザーの ID を Input DTO に詰めて UseCase に渡す
- Policy と Gate は使わない。`app/Policies/` を作らない
- Resource は Output DTO を受け取る。Eloquent モデルを受け取らない

## UseCase 層

- UseCase は Admin / Web で分けず、ドメイン単位で共有する。Http の Admin / Web の分割は入出力の形の違いだけを担う
- 1クラス1ユースケース。公開する実行メソッドは `__invoke` のみ
- 認可は UseCase の責務。認可の単位はユースケースなので、モデル単位の認可（Policy）は作らない
- 認可は `__invoke` の冒頭に早期 return で書く。UseCase を読めば誰が実行できるかが分かる状態を保つ
- 「自分のレコードだけ」の所有チェックは認可として書かず、モデルのスコープで取得範囲を絞る（`Task::ownedBy($actorId)`）。見つからない場合は 404 として扱う
- 認可の失敗は業務例外として投げる。HTTP ステータスへの変換は Http 層に任せ、`abort()` や `AuthorizationException` を UseCase で使わない
- 入力は `Dto/` の Input DTO、出力は `Dto/` の Output DTO で受け渡す
- Eloquent モデルを UseCase の外に出さない。返す前に Output DTO に変換する
- HTTP 層の型（FormRequest、Resource、Response、`request()` ヘルパー）を UseCase に持ち込まない
- UseCase から他の UseCase を呼ばない。共有したい処理は Models か Externals のいずれかに寄せる
- トランザクションは UseCase の中で張る。Http 層とモデルでトランザクションを開始しない

## Models 層

- Repository 層は作らない。UseCase から Eloquent モデルを直接使う
- ドメインごとのディレクトリを作らず `Models/` 直下にフラットに置く
- クエリのビルドを UseCase に書かない。条件を伴う取得はモデルのメソッドかスコープとして定義し、UseCase からは名前で呼ぶ
- 単純なデータ取得も、複数箇所で使うならモデルのメソッドとして定義する

## Externals 層

- 外部サービス1つにつきディレクトリを1つ作り、interface・HTTP 実装・DTO の3点を置く
- interface は `〜Client`、HTTP による実装は `Http〜Client` と命名する
- UseCase は interface のみに依存する。実装クラスを直接参照しない
- interface と実装の DI 登録は専用の ServiceProvider に集約する。`AppServiceProvider` に書かない
- 外部サービスの応答形式（JSON、XML など）の解釈は Externals の中に閉じる。パース結果は DTO に変換して返し、レスポンスの構造を呼び出し側に漏らさない
- HTTP クライアントの設定（ベース URL、認証情報、タイムアウト）は実装クラスの中に閉じる

## Exceptions 層

- 業務例外は `app/Exceptions/` に置き、`ApplicationException` を継承する。クラス名は `〜Exception`
- 例外クラスは HTTP ステータスを知らない。持つのは `ErrorCode` だけで、`errorCode()` を実装する以外の
  中身を書かない
- HTTP ステータスへの変換は `app/Http/Errors/ErrorResponseFactory.php` の1箇所に閉じる。
  例外側にステータスを持たせない
- `ErrorCode` の backing 値は `docs/api/shared/error.yml` の `ErrorCode` enum と一致させる
- コンストラクタの第1引数（`getMessage()`）はログ用の内部詳細。レスポンスには出ない
- クライアントに返す文言は `ErrorCode::defaultMessage()` が既定。ドメイン固有の文言を返したいときだけ
  `clientMessage` 引数で上書きする
- Models / Externals から上がってきたエラーを UseCase でラップするときは `previous` に元の例外を渡す
- 想定外の例外はラップしない。`App\Exceptions` に定義されていない例外はすべて 500 `INTERNAL_ERROR`
  になる（`ValidationException` は 400、ルート未定義は 404 に変換する例外的な2つのみ）

```php
// OK 未存在
$article = Article::ownedBy($input->actorId)->find($input->articleId);

if ($article === null) {
    throw new NotFoundException("article {$input->articleId} not found");
}

// OK 認可失敗
if (! $author->canPublish()) {
    throw new PermissionDeniedException("author {$author->id} cannot publish");
}

// OK 状態競合。クライアントに固有の文言を返す
if ($article->isPublished()) {
    throw new ConflictException(
        "article {$article->id} is already published",
        clientMessage: '公開済みの記事は編集できません。',
    );
}

// OK 外部サービスのエラーをラップする
try {
    $this->slack->postMessage($message);
} catch (ConnectionException $e) {
    throw new ConflictException('slack is unavailable', previous: $e);
}
```

```php
// NG 内部詳細をクライアントに返している
throw new NotFoundException(clientMessage: $e->getMessage());

// NG 例外クラスに HTTP ステータスを持たせている
class NotFoundException extends ApplicationException
{
    public function status(): int
    {
        return 404;
    }
}
```
