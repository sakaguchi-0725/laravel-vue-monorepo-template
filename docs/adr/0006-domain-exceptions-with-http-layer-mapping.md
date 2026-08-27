---
status: accepted
date: 2026-08-27
scope: api
---

# ADR-0006: エラーを独自例外で表し HTTP 変換を Http 層に閉じる

## 背景

api が返すエラーレスポンスは OpenAPI（`docs/api/shared/error.yml`）で `{code, message}` の2フィールドに固定され、
`code` は5種の enum に限定されている（[ADR-0002](./0002-openapi-as-single-api-contract.md)）。  
api は Http / UseCases / Models の3層で、UseCases は HTTP を知らない（[ADR-0004](./0004-three-layer-structure-for-api.md)）。  
`message` は利用者に表示される日本語で、調査に使う内部メッセージとは別に必要になる。  
一方 Laravel は入力検証・モデル未存在・認可失敗などに標準例外を持ち、それぞれ HTTP ステータスへの変換を既定で行う。

## 判断基準

- OpenAPI で定義した `code` / `message` のとおりに返せること
- UseCases が HTTP を知らない状態を保てること

## 検討した選択肢

- 独自の例外階層（`ApplicationException`）を UseCases から投げ、HTTP への変換を Http 層に閉じる
- Laravel / Symfony の標準例外を直接投げ、`withExceptions` でレスポンス形式だけ整える

## 決定

**独自の例外階層を UseCases から投げ、HTTP への変換を Http 層の `ErrorResponseFactory` に閉じる。**

標準例外を直接投げる案は、UseCases が Symfony の HTTP 例外型に依存することになり、3層の分離が崩れる。  
また標準例外は利用者向けの文言を持たず、`getMessage()` は内部向けのため、そのまま返すと OpenAPI の `message` と食い違う。

Http 層まで届いた標準例外も同じ `{code, message}` に写す。  
フレームワークが直接投げたものは例外自身のステータスを維持し、`ModelNotFoundException` のように Http 層まで漏れた時点で
実装バグとみなせるものだけ 500 として扱う。

## 影響

- 良い: UseCases は `ErrorCode` という業務語彙だけで失敗を表現でき、HTTP ステータスを知らない
- 良い: 利用者向けの文言を例外に持たせられ、内部メッセージをレスポンスに出さずに済む
- 良い: 標準例外も同じ形式に写るため、クライアントが扱うエラーレスポンスが1種類に揃う
- 悪い: 標準例外のステータスを維持するために、Http 層が Laravel の詰め替え仕様（`prepareException` が元の例外を `previous` に残すこと）に依存する。フレームワークの更新で壊れうる
- 悪い: 業務エラーの種類を増やすたびに `ErrorCode` と OpenAPI の enum を両方更新する必要がある
- 中立: 認証・認可の標準例外をどのステータスで返すかは、認証方式を決めるまで保留する

## 参考

- `docs/api/shared/error.yml`（レスポンススキーマと `code` の定義）
- `apps/api/tests/Architecture/ExceptionTest.php`（例外が HTTP 型に依存しないことの検証）
