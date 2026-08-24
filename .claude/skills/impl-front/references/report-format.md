# レポートフォーマット

## 実装計画フォーマット

Step 4 でユーザーに報告する。**OK が出るまで実装に着手しない。**

差分は**ファイルツリー形式**で示す。行番号・関数名を列挙しない。レビューする側が見たいのは
「どのレイヤー・スライスに何を足すか」であり、配置の是非を判断できる粒度で書く。

```
## 実装計画: <画面名>

### 対象
- アプリ: apps/web（`docs/api/web/openapi.yml`）
- 画面: タスク一覧 / タスク作成
- エンドポイント: GET /todos（ListTodos）、POST /todos（CreateTodo）
- 受け入れ基準: docs/design/todo/list-todos.md、docs/design/todo/create-todo.md

### 実装方針
- <既存構成に合わせる点、判断が必要だった点を3〜5行で>
- <features に置くか page スライスに閉じるか、shared/ui・@repo/ui に足すかの判断とその理由>

### 作成・変更ファイル

apps/web/src/
  pages/todo-list/
    ui/
      todo-list-page.vue           新規: 一覧画面
      todo-list-page.stories.ts    新規: story + play 関数
    model/
      use-todos.ts                 新規: 一覧取得の composable
    index.ts                       新規: public API
  pages/todo-create/
    ui/
      todo-create-page.vue         新規: 作成画面
      todo-create-page.stories.ts  新規: story + play 関数
    model/
      schema.ts                    新規: 入力の zod スキーマ
      use-todo-form.ts             新規: 送信の composable
    index.ts                       新規: public API
  app/routes/
    todo.ts                        新規: /todos・/todos/new のルート定義
    index.ts                       変更: todoRoutes を追加
  shared/api/
    schema.ts                      変更: api:gen で再生成

### テスト観点
- 主要フロー: 件名を入力して作成すると一覧へ遷移すること
- 入力バリデーション: 件名が空のときエラーが表示され送信されないこと
- APIエラー表示: 作成が 400 を返したとき message が表示されること
- 空状態: タスク0件のとき空メッセージが表示されること

### 保留・確認事項
- <仕様書・設計ドキュメントから判断できなかった点>
```

- 追加・変更ファイルはツリーで示し、テーブルにしない
- 保留・確認事項が無い場合はセクションごと省く
- テスト観点は `test-viewpoints.md` のカタログから該当分を選ぶ。受け入れ基準との対応づけは書かない
- テンプレートのセクション以外を勝手に追加しない

## 完了報告フォーマット

Step 8 で報告する。

```
## 実装完了: <画面名>

### 実装したもの
- /todos — タスク一覧（GET /todos）
- /todos/new — タスク作成（POST /todos）

### テスト
- pages/todo-list Default — pass
- pages/todo-list Empty — pass
- pages/todo-create ValidationError — pass

### format / lint / build
- format: 整形あり（<対象ファイル数>ファイル）
- lint: エラーなし
- build: 型エラーなし

### レビュー
- PASS（指摘なし）
  or
- CRITICAL/HIGH <n>件を修正して PASS。修正内容: <1行で>

### 残タスク
- <未対応の MEDIUM/LOW 指摘、後続に回した実装があれば挙げる>
```

- テストは実行結果をそのまま書く。落ちた story があるのに「pass」と書かない
- `lint` / `build` にエラーが残っている状態で完了報告しない
- 残タスクが無い場合はセクションごと省く
