---
paths:
  - "apps/web/src/**/*.{ts,vue}"
  - "apps/admin/src/**/*.{ts,vue}"
---

# TypeScript コーディングルール

## 型安全

- `any` を使わない。型が不明な値は `unknown` で受け、ナローイングしてから使う
- `as` による型アサーションを使わない。ナローイングで解決する
  - 外部データの検証など避けられない場合のみ、理由をコメントで残す
  - `as unknown as T` は例外なく禁止
  - `as const` はアサーションではないため使ってよい
- 非nullアサーション `!` を使わない。オプショナルチェイニングか早期returnで処理する
- 配列や `Record` へのインデックスアクセスは `undefined` を前提に扱う

## 型述語

- `typeof` / `in` で表現できない判定や、2箇所以上で使うナローイングは型述語関数に切り出す

```ts
const isQuotation = (value: unknown): value is Quotation =>
  typeof value === 'object' && value !== null && 'id' in value
```

- 型述語の宣言と実際の検査内容を一致させる。不一致は `as` と同じく型が嘘をつく
- アサーション関数（`asserts value is T`）を使わない。例外送出が前提の構文のため、
  型述語による早期returnか判別可能ユニオンで表現する

## 型定義

- オブジェクト型は `type` で定義する。`interface` を使わない
- `enum` を使わない。`as const` オブジェクトとユニオン型で表現する

```ts
const QuotationStatus = { Draft: 'draft', Sent: 'sent' } as const
type QuotationStatus = (typeof QuotationStatus)[keyof typeof QuotationStatus]
```

- 状態は判別可能ユニオンで表現し、`switch` では `never` への代入で網羅性を検査する

```ts
type FetchState =
  | { status: 'loading' }
  | { status: 'success'; data: Quotation }
  | { status: 'error'; message: string }
```

## 構文

- 関数は関数式（アロー関数）で定義する。`function` 宣言を使わない
- 変数宣言は `const` を既定とし、再代入が必要な場合のみ `let`。`var` を使わない
- 比較は常に `===` / `!==` を使う。`null` チェックを含め `==` / `!=` は使わない

## モジュール

- 型のみの import / export には `import type` / `export type` を付ける

## ファイル名

- ファイル名はケバブケースで統一する

## コメント

- **コメントは書かない。これを既定とする**
- 何をしているか（What）を説明するコメントは絶対に書かない。コードを読めば分かる内容の重複でしかない
- 処理の要約、引数の説明、セクション区切り、`// 〜を取得`・`// 〜を設定` の類はすべて What であり禁止
- 例外はなぜそうしたか（Why）がコードから読み取れない場合のみ。次に限る
  - コードからは導けない業務上の制約や仕様判断
  - 一見不要・不自然に見える処理を残している理由
  - 回避策とその原因（ライブラリの制約、既知の不具合）
- 迷った場合は書かない。命名で説明できることは命名で表現する
- JSDoc も同じ基準で扱う。型情報の再掲は書かない

```ts
// NG（What）: 明細の金額を合計する
const total = items.reduce((sum, item) => sum + item.price, 0)

// OK（Why）: 消費税は請求書発行時に確定するため、見積時点では税抜で合算する
const total = items.reduce((sum, item) => sum + item.price, 0)
```
