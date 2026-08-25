---
status: accepted
date: 2026-08-25
scope: infra
---

# ADR-0001: web / admin / api を単一リポジトリに置く

## 背景

エンドユーザー向け（web）と管理者向け（admin）は同じデザインを使い、フロントエンドのアーキテクチャも同一にする前提だった。  
両アプリの ESLint / Prettier のルールは揃える必要があり、片方だけ設定がずれる状態は避けたい。  
バックエンドは Laravel の単一アプリ（api）で、web と admin の両方に API を提供する。

## 判断基準

- web と admin で lint / format ルールと共通 UI が一元管理されること
- api を含む横断的な変更を1つの PR で完結できること

## 検討した選択肢

- web / admin / api を単一リポジトリに置く
- フロントとバックエンドでリポジトリを分離する（フロント側は web / admin を pnpm workspace でまとめる）

## 決定

**web / admin / api を単一リポジトリに置く** ことにする。

分離案でも web と admin の設定統一は達成できるが、api を跨ぐ変更が2リポジトリに分割され、1つの PR にまとめられないため。

## 影響

- 良い: ESLint 設定を `packages/eslint-config`、Prettier と依存バージョンをルートの設定と pnpm catalog に集約でき、web と admin のルールが構造的にずれない
- 良い: 共通 UI を `packages/ui` に置いて両アプリから参照でき、パッケージの公開やバージョン管理が要らない
- 良い: API 変更とフロント側の追従を1つの PR でレビューできる
- 悪い: PHP と Node のツールチェーンが1リポジトリに同居し、言語ごとの実行環境とタスク定義が混在するため、初見時の把握コストが上がる
- 中立: api は pnpm workspace の対象外で、依存管理は Composer と pnpm に分かれたままになる
