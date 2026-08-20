# コミット承認依頼フォーマット

文面は変更してよい。コミットヘッダーの一覧は必ず表示する。

```
以下のコミットを作成します

- feat: hogehoge
- refactor: hogehoge

これで問題ないですか？（y/n）
```

# コミット完了報告フォーマット

文面は変更してよい。コミットヘッダーの一覧は必ず表示する。

```
コミットを作成しました

- feat: hogehoge
- refactor: hogehoge

pushしますか？（y/n）
```

# PreCommitフック失敗報告フォーマット

失敗の原因を特定して報告するだけにとどめる。修正はしない。後続のコミットも実行しない。

```
pre-commit フックが失敗したため、コミットを中断しました

- 失敗したコミット: feat: hogehoge
- 失敗したフック: lint
- 原因: apps/web/src/features/example/ui/Form.vue で未使用の import

対応方針を指示してください
```
