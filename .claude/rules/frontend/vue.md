---
paths:
  - "apps/web/src/**/*.vue"
  - "apps/admin/src/**/*.vue"
---

# Vue.js コーディング規約

## コンポーネント定義

- props は型ベースで宣言する
- **props は原則すべて必須にする**。省略可能にするのは、値が存在しないこと自体に意味がある場合だけ
- **デフォルト値を指定しない**。デフォルト値は呼び出し側から見えない暗黙の仕様になり、渡し忘れも検出できなくなる
  - 例外は Shared 層の汎用コンポーネントのみ。ドメイン固有のコンポーネントでは使わない
  - 指定する場合は分割代入のネイティブ構文で与える。`withDefaults` を使わない

```vue
<script setup lang="ts">
const { label, size = 'md' } = defineProps<{ label: string; size?: 'sm' | 'md' }>()
</script>
```

- 分割代入した props を `watch` や composable に渡すときはゲッターで包む（`watch(() => size, ...)`）

- 双方向バインディングは `defineModel` で定義する。`modelValue` prop と `update:modelValue` emit を手書きしない

```ts
const value = defineModel<string>()
```

## リアクティビティ

- 状態は `ref` で定義する。`reactive` を使わない
  - `reactive` は再代入でリアクティブ接続が切れ、分割代入でリアクティビティを失うため
- 派生値は `computed` で表現する。`watch` で他の状態へ同期しない
- 監視対象を列挙できるなら `watch`、依存が多くネストしていて列挙が難しいなら `watchEffect`

## テンプレート

- `v-for` と `v-if` を同じ要素に置かない。`computed` で絞り込むか `<template v-for>` で分離する

```vue
<!-- NG: v-if が先に評価されるため user が存在しない -->
<li v-for="user in users" v-if="user.isActive" :key="user.id">

<!-- OK -->
<li v-for="user in activeUsers" :key="user.id">
```

- `v-for` の `key` に index を使わない。要素を一意に識別する値を渡す
  - 並べ替えや削除の際に、DOM とコンポーネントの状態が別の要素にずれるため
- テンプレート内のコンポーネントタグはパスカルケースで書く
- `v-html` にユーザー入力を渡さない

## ファイル

- ファイル名はケバブケースで統一する（`task-list-item.vue`）
- SFC のブロック順は `<script setup>` → `<template>` → `<style>` で統一する

## スタイル

- `<style scoped>` でコンポーネントスコープに閉じる。アプリ全体に効かせるスタイルは App 層に置く
