---
status: accepted
date: 2026-08-25
scope: shared
---

# ADR-0003: web / admin で API を分ける

## 背景

api は web と admin の2つのフロントエンドに対して API を提供し、同じリソース（Todo など）を双方が扱う。  
契約は OpenAPI で定義し、web / admin はそこから openapi-typescript で型を生成する（[ADR-0002](./0002-openapi-as-single-api-contract.md)）。  
生成された型は OpenAPI の `paths` をキーに引くため、1つのスキーマに両方のエンドポイントが載ると、web 用のクライアントからも admin 用のパスが候補に出る。

## 判断基準

- 各フロントエンドの型が、そのフロントエンドが呼べるエンドポイントだけに閉じること

## 検討した選択肢

- エンドポイント・Request・Resource を web / admin で分け、OpenAPI も2本に分ける
- api 側は分けたうえで OpenAPI を1本にまとめる
- エンドポイント・Request・Resource を分けず、1系統を web / admin の双方が呼ぶ

## 決定

**エンドポイント・Request・Resource を web / admin で分け、OpenAPI も2本に分ける。**

残る2案はいずれも1つのスキーマに web と admin のパスが載る。  
生成された型が統合され、web 用のクライアントから admin のエンドポイントが型エラーなしに補完・推論されてしまうため（逆も同様）。

## 影響

- 良い: web / admin それぞれの生成型が自分の呼べるエンドポイントだけを含み、他方のパスを補完・推論に出さない
- 良い: 一方のレスポンス項目を変えても他方の契約に波及しない
- 悪い: 内容がほぼ同じ Request / Resource が web 側と admin 側に二重に存在し、項目追加のたびに両方を直すことになる
- 悪い: OpenAPI も web / admin の2本を書く必要があり、共通化する場合は `docs/api/shared/` へ切り出す判断が都度発生する
- 中立: 分けるのは HTTP 層（エンドポイント・Request・Resource）までで、その内側の UseCase は共有する
