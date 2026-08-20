---
paths:
  - "apps/api/app/**/*.php"
---

# バックエンドアーキテクチャ

## レイヤー

`apps/api/app/` 配下は以下のレイヤーで構成する。

- `Http/`: HTTP の入出力。リクエストの受け取り、認可、レスポンスの整形
- `UseCases/`: ユースケース1つ分の処理。業務ロジックとトランザクション境界
- `Models/`: Eloquent モデル。永続化とデータ取得
- `Externals/`: 外部サービス連携
- `Providers/`: サービスコンテナへの登録

依存方向は `Http/` → `UseCases/` → `Models/` / `Externals/` の一方向のみ。逆方向の参照を作らない。

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
  Externals/
    ServiceA/
      Dto/
      ServiceAClient.php
      HttpServiceAClient.php
  Providers/
    ExternalServiceProvider.php
```

## Http 層

- `Http/` の直下は Admin / Web で分け、その下をドメインで分ける
- コントローラーが使う FormRequest と Resource は、そのコントローラーと同じドメインディレクトリの `Requests/` `Resources/` に置く。`Http/Requests/` `Http/Resources/` のような技術的関心を最上位に置いたディレクトリは作らない
- Admin と Web で Request / Resource を共有しない。同じ形になっていても、公開する項目とバリデーションは画面ごとに変わるため、それぞれの配下に定義する
- コントローラーの責務は「リクエストを UseCase の Input DTO に変換する」「UseCase を呼ぶ」「戻ってきた Output DTO を Resource に渡す」の3つのみ。それ以外の処理を書かない
- 認可は Http 層で完結させる。Policy かミドルウェアで表現し、UseCase に持ち込まない
- Resource は Output DTO を受け取る。Eloquent モデルを受け取らない

## UseCase 層

- UseCase は Admin / Web で分けず、ドメイン単位で共有する。Admin と Web の権限差は Http 層で吸収する
- 1クラス1ユースケース。公開する実行メソッドは `__invoke` のみ
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
