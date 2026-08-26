---
status: accepted
date: 2026-08-26
scope: shared
---

# ADR-0005: フロントエンドに Feature-Sliced Design を採用する

## 背景

web / admin はどちらも Vue 3 の SPA で、同じディレクトリ構成の規約に従う（[ADR-0001](./0001-single-repository-for-web-admin-api.md)）。  
本リポジトリはテンプレートであり、最初の実装者以外がこの構成の上に画面を足していく。  
チームメンバーには Feature-Sliced Design（以下 FSD）の実務経験がある。

## 判断基準

- チームがすでに持っている経験を使えること
- 規約の学習を口伝や既存コードの模倣に頼らず、公開ドキュメントに委ねられること
- 依存方向の違反を人のレビューではなく linter で落とせること

## 検討した選択肢

- Feature-Sliced Design
- 技術別分割（`components/` / `composables/` / `views/`）

## 決定

**Feature-Sliced Design** を採用する。

技術別分割は依存方向を規約として表現できず、`components/` 同士の相互参照や `composables/` から画面固有の状態を参照する経路を機械的に禁止できない。  
FSD はレイヤーとスライスがディレクトリ構造と一致するため、その境界をそのまま lint ルールに落とせる。

## 影響

- 良い: レイヤー間の依存方向とスライス間の参照禁止を eslint-plugin-boundaries で強制でき、違反がレビュー前に落ちる
- 良い: 規約の説明を FSD の公式ドキュメントに委ねられ、リポジトリ固有の規約として書き起こす量が減る
- 悪い: FSD 未経験者には、レイヤー・スライス・セグメントの3階層を先に把握する学習が必要になる
- 悪い: ファイルの置き場所がレイヤーとスライスの両方で決まるため、Vue の一般的な構成を想定して読み始めた人には初見でたどりにくい
- 中立: 使用するレイヤーは app / pages / features / shared の4つ。entities / widgets は現時点で使っていない

## 参考

- [Feature-Sliced Design 公式ドキュメント](https://feature-sliced.design/)
- `.claude/rules/frontend/fsd.md`（レイヤー・スライス・セグメントの取り決め）
- `packages/eslint-config/src/fsd.ts`（依存方向とスライス分離の lint ルール）
