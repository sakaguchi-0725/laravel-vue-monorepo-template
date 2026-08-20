---
name: adr
description: |
  アーキテクチャ決定記録（ADR）を docs/adr/ に作成・更新するスキル。
  技術選定や構造の決定について「なぜそう決めたか」「他に何を検討して却下したか」を
  1決定1ファイルの Markdown として残し、README.md の一覧テーブルも更新する。

  以下の文脈で必ず使うこと:
  - 「ADR を書いて」「ADR に残して」「決定記録を作って」
  - 「技術選定の理由を残して」「なぜこの構成にしたか記録して」「判断の根拠をドキュメント化して」
  - 「〜を採用することにした」「〜を使うことにしたので記録して」
  - 「この ADR を Superseded にして」「方針が変わったので ADR を更新して」
  - フレームワーク・ライブラリ・DB・認証方式・レイヤ構成・CI 構成などを決めた直後
  - docs/adr/ 配下のファイルを新規作成・編集するとき
---

# adr スキル

アーキテクチャ上の決定とその理由を `docs/adr/` に記録する。

## ゴール

- 決定そのものではなく、**なぜその決定に至ったか**を残す
- 却下した選択肢とその理由を残し、同じ議論の再燃を防ぐ
- 受け入れたコスト（悪い影響）を明示し、後任が判断をやり直せる状態にする

---

## 参照ファイル（必読）

このスキルの判断基準・質問項目・記述ルールはすべて `references/` にある。
**該当 Step に入る前に必ず Read する。** 記憶や推測で代替しない。

| ファイル | 読むタイミング | 用途 |
| --- | --- | --- |
| [`references/decision-criteria.md`](./references/decision-criteria.md) | Step 1・Step 3 の前 | ADR 化すべき決定か / scope はどれか |
| [`references/question-format.md`](./references/question-format.md) | Step 4 の前 | 何を聞くか・どうまとめて聞くか |
| [`references/guidelines.md`](./references/guidelines.md) | Step 5 の前 | 書き方の禁則（良い例／悪い例） |

`docs/adr/README.md` は**読み手向け**（status / scope の意味と一覧）であり、書き方は載っていない。
書く側のルールはこのスキルが持つ。

---

## Step 1: ADR を書くべき決定か判定

**[`references/decision-criteria.md`](./references/decision-criteria.md) の A 節を Read してから判定する。**

構造 / 非機能要件 / 依存関係 / インターフェース / 構築技術のいずれかに該当し、かつ変更コストが高いなら対象。

対象外だった場合は、**黙って別の場所に書かず、理由と適切な書き場所を示して終了する。**
差し戻しの例文は decision-criteria.md A 節にある。

---

## Step 2: 採番

`docs/adr/` の既存ファイルを確認し、**最大番号 + 1** を4桁で採番する（`0000-template.md` は除外）。

- 番号は再利用しない。削除された ADR があっても欠番のままにする
- ファイル名は `NNNN-{英語ケバブケース}.md`（例: `0003-adopt-sqlc.md`）
- 本文の見出しは日本語、ファイル名だけ英語ケバブケース（`docs/design/` の既存規約に合わせる）

---

## Step 3: scope 判定

**[`references/decision-criteria.md`](./references/decision-criteria.md) の B 節を Read してから判定する。**

`web` / `admin` / `api` / `shared` / `infra` から選ぶ。「どのファイルが変わるか」ではなく
**どちらが決定に拘束されるか**で決める。迷う境界例は B 節に一覧がある。

---

## Step 4: 不足情報を一括確認

**[`references/question-format.md`](./references/question-format.md) を Read してから質問する。**

却下した案・却下理由・判断基準・受け入れたコストはコードにもドキュメントにも残っておらず、
ユーザーに聞くしかない。ここを飛ばすとメリットだけが並んだ ADR になる。

**1問ずつ聞かず、1メッセージにまとめる。** 推測できる部分は推測を提示して確認する形にする。

すでに会話から読み取れる項目は聞かない。回答が得られなかった項目の扱いも question-format.md に従う
（**推測で選択肢を水増ししない**）。

---

## Step 5: ADR ファイルの生成

**[`references/guidelines.md`](./references/guidelines.md) を Read し、全項目に従って書く。**

[`docs/adr/0000-template.md`](../../../docs/adr/0000-template.md) をコピーして `docs/adr/NNNN-*.md` を作る。

要点:

1. 1 ADR = 1 決定。複数混ざっていたら分割する
2. 「背景」は中立の事実（制約・体制・期限）。宣伝文句を書かない
3. 「影響」に **悪い影響を必ず書く**。思いつかなければ学習コスト・依存増加・ロックインを疑う
4. 「検討した選択肢」は実在した案だけ。当て馬を並べない
5. 手順・設定方法を書かない（README / OpenAPI の担当）
6. 現在の全体構成を書かない。ADR はその時点の1決定の記録
7. 1〜2ページに収める
8. 複数文が続く箇所は文末（`。`）に半角スペース2つ

### front matter

| キー | 値 | 必須 |
| --- | --- | --- |
| `status` | `proposed` / `accepted` / `rejected` / `deprecated` / `superseded` | 必須 |
| `date` | 決定日（`YYYY-MM-DD`） | 必須 |
| `scope` | `web` / `admin` / `api` / `shared` / `infra` | 必須 |
| `supersedes` | 置き換える ADR 番号（例: `0003`） | 該当時のみ |
| `superseded-by` | 置き換えられた先の ADR 番号 | 該当時のみ |

該当しないキーは**キーごと削除**する（空欄で残さない）。
`status` はユーザーが決定済みと明言していない限り `proposed` にする（Step 4 で確認する）。

---

## Step 6: README.md の一覧に追記

[`docs/adr/README.md`](../../../docs/adr/README.md) 末尾の一覧テーブルに1行足す。

```
| 0003 | sqlc を採用する | server | Accepted | 2026-08-08 |
```

- No は ADR 本文へのリンクにする: `| [0003](./0003-adopt-sqlc.md) | ... |`
- 番号順に並べる
- `0000-template.md` は一覧に載せない

---

## Step 7: Superseded 処理（該当時のみ）

既存の ADR を置き換える場合:

1. 新 ADR の front matter に `supersedes: {旧番号}` を設定
2. 旧 ADR の front matter を `status: superseded` / `superseded-by: {新番号}` に更新
3. **旧 ADR の本文は書き換えない。** 当時の判断が残ることが ADR の価値
4. README.md の一覧で旧 ADR の Status を `Superseded` に更新

`rejected` になった ADR も削除しない。同じ案の再提案時に議論を繰り返さずに済む。

---

## Step 8: 品質チェック（生成後に確認）

- [ ] [`references/guidelines.md`](./references/guidelines.md) の全項目に従っている
- [ ] 1 ADR に決定が1つだけ含まれている
- [ ] 「影響」に「悪い:」の行がある
- [ ] 「検討した選択肢」が実際に検討した案だけになっている
- [ ] 「背景」に宣伝文句・形容詞による価値主張が無い
- [ ] 手順・セットアップ方法が入っていない
- [ ] 全体が1〜2ページに収まっている
- [ ] front matter の `status` / `date` / `scope` が埋まり、不要な `supersedes` / `superseded-by` が残っていない
- [ ] ファイル名が `NNNN-{英語ケバブケース}.md` で、番号が既存と重複していない
- [ ] README.md の一覧テーブルに行が追加され、リンクが正しい
- [ ] （置き換えがある場合）旧 ADR の status と README の Status が更新され、旧 ADR の本文は無変更
