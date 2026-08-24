<template>
  <main class="page">
    <h1 class="title">タスク一覧</h1>

    <RouterLink :to="{ name: 'example' }" class="link">サンプルフォームへ</RouterLink>
  </main>
</template>

<style scoped>
.page {
  max-width: 32rem;
  margin-inline: auto;
  padding: 2rem 1rem;
}

.title {
  font-size: 1.5rem;
  font-weight: 700;
  margin-bottom: 1.5rem;
}

.link {
  color: #4f46e5;
}
</style>
