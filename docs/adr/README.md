# アーキテクチャ決定記録（ADR）

技術選定や構造の決定と、**なぜそう決めたか**を1決定1ファイルで残す。  
作成・更新は `/adr` スキルで行う。

### status

| 値 | 意味 |
| --- | --- |
| `proposed` | 提案段階。まだ従う必要はない |
| `accepted` | 有効な決定。現行の方針 |
| `rejected` | 検討したが採用しなかった。同じ案を再提案する前に読む |
| `deprecated` | 非推奨。後継となる ADR は無い |
| `superseded` | 別の ADR に置き換えられた。`superseded-by` が後継の番号 |

`accepted` 以降、本文は書き換えられない。  
方針が変わった場合は新しい ADR が起こされ、古い ADR は `superseded` として残る。  
古い ADR を読むときは、**それが書かれた時点の判断である**ことに注意する。

### scope

| 値 | 対象 |
| --- | --- |
| `web` | apps/web 内で完結する決定 |
| `admin` | apps/admin 内で完結する決定 |
| `api` | apps/api 内で完結する決定 |
| `shared` | 複数のアプリが拘束される決定（API 契約、packages/ 配下の共有コード） |
| `infra` | アプリ外（CI、Docker、モノレポツール、デプロイ先） |

## 一覧

| No | タイトル | scope | Status | 日付 |
| --- | --- | --- | --- | --- |
| [0001](./0001-single-repository-for-web-admin-api.md) | web / admin / api を単一リポジトリに置く | infra | Accepted | 2026-08-25 |
| [0002](./0002-openapi-as-single-api-contract.md) | OpenAPI を唯一の API 契約とする | shared | Accepted | 2026-08-25 |
| [0003](./0003-separate-api-surface-for-web-and-admin.md) | web / admin で API を分ける | shared | Accepted | 2026-08-25 |
| [0004](./0004-three-layer-structure-for-api.md) | api を Http / UseCases / Models の3層にする | api | Accepted | 2026-08-25 |
| [0005](./0005-adopt-feature-sliced-design-for-frontend.md) | フロントエンドに Feature-Sliced Design を採用する | shared | Accepted | 2026-08-26 |
