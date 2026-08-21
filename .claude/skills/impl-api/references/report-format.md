# レポートフォーマット

## 実装計画フォーマット

Step 4 でユーザーに報告する。**OK が出るまで実装に着手しない。**

```
## 実装計画: <ユースケース名>

### 対象
- ドメイン: article
- 呼び出し元: apps/web（`docs/api/web/openapi.yml`）
- エンドポイント: POST /articles — 記事を作成する（operationId: CreateArticle）
- 受け入れ基準: docs/design/article/create-article.md UC-001

### 実装方針
- <既存構成に合わせる点、判断が必要だった点を3〜5行で>
- <既存に無いパターンを新設する場合は、その理由>

### 作成・変更ファイル

apps/api/
  database/migrations/
    YYYY_MM_DD_HHMMSS_create_articles_table.php  新規: articles テーブル
  app/
    Models/
      Article.php              新規: モデル・スコープ
      ArticleStatus.php        新規: ステータスの enum
    UseCases/Article/
      Dto/
        CreateArticleInput.php   新規
        ArticleOutput.php        新規
      CreateArticleUseCase.php   新規
    Http/Web/Article/
      Requests/
        StoreArticleRequest.php  新規
      Resources/
        ArticleResource.php      新規
      ArticleController.php      新規
  routes/api.php               変更: POST /articles を追加
  database/factories/
    ArticleFactory.php         新規: テスト用ファクトリ
  tests/Feature/Web/Article/
    CreateArticleTest.php      新規: Feature テスト

### テスト観点
- 記事を作成して 201 と作成後のリソースを返すこと
- 作成直後のステータスが draft になること
- <取得範囲・並び順など、仕様書と受け入れ基準から導いた観点>

### 保留・確認事項
- <仕様書に定義が無く判断できなかった点>
- <新しい ErrorCode の追加が必要な場合はここに挙げて確認を取る>
```

保留・確認事項が無い場合はセクションごと省く。

## 完了報告フォーマット

Step 8 で報告する。

```
## 実装完了: <ユースケース名>

### 実装したもの
- POST /articles — 記事を作成する

### テスト
- CreateArticleTest::投稿者が記事を作成できること — pass
- CreateArticleTest::作成直後のステータスがdraftになること — pass

### format / 静的解析
- format: 整形あり（<対象ファイル数>ファイル）
- lint: エラーなし（PHPStan level max / PHPat）

### レビュー
- PASS（指摘なし）
  or
- CRITICAL/HIGH <n>件を修正して PASS。修正内容: <1行で>

### 残タスク
- <エラークラス整備後に追加するテスト・実装があれば挙げる>
```

- テストは実行結果をそのまま書く。落ちたテストがあるのに「pass」と書かない
- `lint` にエラーが残っている状態で完了報告しない
- 残タスクが無い場合はセクションごと省く
