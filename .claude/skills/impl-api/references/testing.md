# テスト

`apps/api` のテストは **Feature テスト（HTTP 経由）を基本**とする。
コントローラー・UseCase・モデルを個別に単体テストしない。
Spectator の契約検証（実レスポンス ↔ OpenAPI 仕様書）は HTTP 経由でしか働かない。

単体テストは依頼されたときだけ書く。振る舞いが自明なコードや、フレームワーク・
外部パッケージの機能を再テストするだけのテストは書かない。

## 目次

- [配置と命名](#配置と命名)
- [テストクラスの骨格](#テストクラスの骨格)
- [ファクトリ](#ファクトリ)
- [何を検証するか](#何を検証するか)
- [外部サービスの差し替え](#外部サービスの差し替え)
- [実行](#実行)

---

## 配置と命名

`Http/` の構成をそのまま写す。`Web` / `Admin` は突き合わせる仕様書が別（`web/openapi.yml` /
`admin/openapi.yml`）なので分ける。

```
tests/Feature/
  WebTestCase.php     # Web 用の基底クラス
  AdminTestCase.php   # Admin 用の基底クラス
  Web/
    Article/
      ListArticlesTest.php
      CreateArticleTest.php
  Admin/
    Article/
      ListArticlesTest.php
```

- 1エンドポイント1クラス。クラス名は OpenAPI の `operationId` + `Test`
- テストメソッドは `#[Test]` 属性を付け、名前は**受け入れ基準のシナリオタイトルをそのまま**使う。
  `test` プレフィックスは付けない

```php
// OK 受け入れ基準のシナリオタイトルがそのまま入っている
#[Test]
public function 投稿者が記事を作成できること(): void

#[Test]
public function 他の投稿者の記事は一覧に含まれないこと(): void

#[Test]
public function 公開日が未設定の場合、一覧の末尾に並ぶこと(): void

// NG 何を検証しているか読めない
#[Test]
public function store(): void

#[Test]
public function error(): void
```

PHPUnit は「名前が `test` で始まる」か「`#[Test]` が付いている」のいずれかを満たす public メソッドを
テストとして扱う（`Util/Test.php`）。`#[TestDox]` は Collision が読まないため使わない。

---

## テストクラスの骨格

`Tests\Feature\WebTestCase` / `Tests\Feature\AdminTestCase` を継承する。
基底クラスが `RefreshDatabase` と `Spectator::using()`（`web` / `admin` の仕様書）を持つため、
各テストクラスで書かない。

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Web\Article;

use App\Models\Article;
use App\Models\ArticleStatus;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\WebTestCase;

class CreateArticleTest extends WebTestCase
{
    #[Test]
    public function 投稿者が記事を作成できること(): void
    {
        $author = User::factory()->create();

        $response = $this->actingAs($author)->postJson('/api/articles', [
            'title' => '請求書の書き方',
            'body' => '本文',
            'publishedAt' => '2026-09-30',
        ]);

        $response->assertValidRequest()
            ->assertValidResponse(201);

        $this->assertSame('請求書の書き方', $response->json('title'));
        $this->assertSame(ArticleStatus::Draft->value, $response->json('status'));

        $this->assertDatabaseHas('articles', [
            'author_id' => $author->id,
            'title' => '請求書の書き方',
        ]);
    }
}
```

| 項目 | 補足 |
| --- | --- |
| レスポンスは `data` で包まれない | `AppServiceProvider::boot()` の `JsonResource::withoutWrapping()` が前提。外すと仕様書のスキーマと一致せず `assertValidResponse()` が落ちる |
| リクエストパスは `/api/` 付き | 実際のルートは `/api` 配下。仕様書側の `/api` は `SPECTATOR_PATH_PREFIX=api` が除去する |
| `assertValidRequest()` → `assertValidResponse(<status>)` | この順に呼ぶ |
| `assertValidResponse()` に期待ステータスを渡す | 内部で `assertStatus()` も行う。`assertCreated()` などを別に呼ばない |
| `$this->assertSame()` | `tests` は PHPStan の解析対象外。`static::` に揃えない |

---

## ファクトリ

テストデータは `database/factories/` のファクトリで作る。テストの中で `Article::create([...])` を
直に並べない。

```php
// OK 検証に関係する値だけを明示し、残りはファクトリの既定値に任せる
$article = Article::factory()->for($author, 'author')->create(['title' => '請求書の書き方']);

// OK 状態はステートメソッドで表す
$article = Article::factory()->published()->create();

// NG 検証に関係ない値まで全部書いている（何が本質か読めない）
$article = Article::factory()->create([
    'author_id' => $author->id,
    'title' => 'タイトル',
    'body' => '本文',
    'status' => 'draft',
    'published_at' => null,
]);
```

```php
// OK ファクトリ側に状態を定義する
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Article;
use App\Models\ArticleStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'body' => fake()->paragraph(),
            'status' => ArticleStatus::Draft,
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ArticleStatus::Published,
            'published_at' => now(),
        ]);
    }
}
```

---

## 何を検証するか

`docs/design/{domain}/{use-case}.md` の受け入れ基準（Given/When/Then）が1シナリオ1テストメソッドになる。
受け入れ基準に無い観点を勝手に増やす前に、まず全シナリオを網羅する。
入力値の境界は OpenAPI 仕様書の `required` / `enum` / `minLength` / `maxLength` から導く。

必ず入れる観点。

- **正常系** — 期待するステータス・ボディ・DB の状態
- **仕様書との整合** — `assertValidRequest()` / `assertValidResponse()`
- **取得範囲の絞り込み** — 他人のレコード・削除済み・非公開が漏れないこと
- **並び順・絞り込み条件** — 仕様書の `description` に書かれた順序やクエリパラメータの効き方
- **異常系** — バリデーション（必須欠落・`maxLength` + 1 文字・`enum` 外の値）、認可失敗、未存在、状態競合

### 異常系の検証

`ErrorResponseFactory` がステータスとボディを決めるため、テストではステータスと `code` を見る。
`message` を検証するのは `clientMessage` で固有の文言を返す実装のときだけ。

```php
#[Test]
public function 他の投稿者の記事は取得できないこと(): void
{
    $article = Article::factory()->for(User::factory()->create(), 'author')->create();

    $response = $this->actingAs(User::factory()->create())
        ->getJson("/api/articles/{$article->id}");

    $response->assertValidResponse(404);
    $this->assertSame('NOT_FOUND', $response->json('code'));
}

#[Test]
public function 公開済みの記事は編集できないこと(): void
{
    $author = User::factory()->create();
    $article = Article::factory()->for($author, 'author')->published()->create();

    $response = $this->actingAs($author)->patchJson("/api/articles/{$article->id}", ['title' => '変更後']);

    $response->assertValidResponse(409);
    $this->assertSame('CONFLICT', $response->json('code'));
    $this->assertSame('公開済みの記事は編集できません。', $response->json('message'));
}
```

`assertValidResponse()` を通すには、そのステータスが OpenAPI 仕様書の `responses` に
定義されている必要がある。定義が無い場合は仕様書側の不足なのでユーザーに報告する。

500 の網羅は不要。`ErrorResponseFactory` の変換は `tests/Feature/ErrorResponseTest.php` が
検証済みなので、各エンドポイントで再テストしない。

```php
// OK 取得範囲。他人の記事が混ざらないことを明示的に検証する
#[Test]
public function 他の投稿者の記事は一覧に含まれないこと(): void
{
    $author = User::factory()->create();
    Article::factory()->for($author, 'author')->create(['title' => '自分の記事']);
    Article::factory()->for(User::factory()->create(), 'author')->create(['title' => '他人の記事']);

    $response = $this->actingAs($author)->getJson('/api/articles');

    $response->assertValidResponse(200);
    $this->assertCount(1, $response->json('articles'));
    $this->assertSame('自分の記事', $response->json('articles.0.title'));
}

// OK 順序。仕様書に「期限日の昇順、未設定は末尾」と書かれているなら順序まで検証する
$this->assertSame(['先', '後', '未設定'], array_column($response->json('articles'), 'title'));
```

```php
// NG フレームワークの機能を再テストしている
#[Test]
public function 公開日がCarbonにキャストされること(): void
{
    $this->assertInstanceOf(Carbon::class, Article::factory()->published()->create()->published_at);
}

// NG 実装の呼び出し手順を固定していて、振る舞いを検証していない
$useCase = Mockery::mock(CreateArticleUseCase::class);
$useCase->shouldReceive('__invoke')->once();
```

`assertValidResponse()` はスキーマ整合しか見ない。値そのものは別に検証する。

日時を固定したい場合は `travelTo()` を使わない。`tearDown` で戻らずプロセス内の後続テストに漏れる。
`CarbonImmutable::now($tz)->subDay()` のような相対日付で組む。

---

## 外部サービスの差し替え

`Externals` は interface で DI されている。テストではコンテナの束縛を差し替え、
実際のリクエストを飛ばさない。

```php
// OK interface のフェイクを束縛する
$slack = Mockery::mock(SlackClient::class);
$slack->shouldReceive('postMessage')->once();
$this->app->instance(SlackClient::class, $slack);

// OK 失敗時の振る舞いを検証する
$slack->shouldReceive('postMessage')->andThrow(new RuntimeException('slack unavailable'));
```

呼び出し回数（`once()`）を常設しない。原則はレスポンスと DB 状態で検証する。
回数を見るのは**結果に痕跡が残らない外部副作用**を保証したいときだけ
（二重送信の防止、「送らないこと」の保証）。

`Http::fake()` を使うのは `Http〜Client` 自体をテストするときだけ。UseCase のテストで
HTTP レイヤーまで踏むと、外部サービスの URL やペイロード形式にテストが結合する。

---

## 実行

```bash
mise -C apps/api run test
```

`--parallel` で並列実行される。特定のテストだけ動かす場合も Docker 経由で実行する。

```bash
docker compose exec api php artisan test --filter=CreateArticleTest
```

Spectator の指摘（`Response validation failed` など）は実装を直して解消する。
仕様書を実装に合わせて書き換えない。仕様書側が誤っていると判断した場合はユーザーに確認する。
