# アーキテクチャのサンプルコード

`.claude/rules/backend/architecture.md` のルールに則ったコード例。
サンプルのドメインは `article`（記事）、アクターは投稿者とする。

依存方向は `Http/` → `UseCases/` → `Models/` / `Externals/` の一方向のみ。
この方向は PHPat（`tests/Architecture/LayerDependencyTest.php`）が機械的に検査する。

## 目次

- [Http 層](#http-層) — コントローラー / FormRequest / Resource
- [UseCase 層](#usecase-層) — DTO / `__invoke` / 認可 / トランザクション
- [Models 層](#models-層)
- [Externals 層](#externals-層)
- [Exceptions 層](#exceptions-層)
- [ルーティング](#ルーティング)

---

## Http 層

### コントローラー

責務は「リクエストを Input DTO に変換する」「UseCase を呼ぶ」「Output DTO を Resource に渡す」の3つだけ。
基底クラスは継承しない。ドメインディレクトリ直下に置くのはコントローラーのみ
（PHPat の `test_controllers_are_named_with_controller_suffix` が名前で検査する）。

```php
// OK
<?php

declare(strict_types=1);

namespace App\Http\Web\Article;

use App\Http\Web\Article\Requests\StoreArticleRequest;
use App\Http\Web\Article\Resources\ArticleResource;
use App\UseCases\Article\CreateArticleUseCase;
use App\UseCases\Article\Dto\CreateArticleInput;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ArticleController
{
    public function store(StoreArticleRequest $request, CreateArticleUseCase $useCase): JsonResponse
    {
        $input = new CreateArticleInput(
            authorId: $request->user()->id,
            title: $request->string('title')->toString(),
            body: $request->string('body')->toString(),
            publishedAt: $request->date('publishedAt'),
        );

        return ArticleResource::make($useCase($input))
            ->response()
            ->setStatusCode(201);
    }

    public function index(Request $request, ListArticlesUseCase $useCase): JsonResponse
    {
        $output = $useCase(new ListArticlesInput(
            authorId: $request->user()->id,
            status: ArticleStatus::tryFrom((string) $request->query('status')),
        ));

        return ArticleCollectionResource::make($output)->response();
    }
}
```

```php
// NG コントローラーに業務ロジックを書いている
public function store(StoreArticleRequest $request): JsonResponse
{
    if (Article::where('author_id', $request->user()->id)->count() >= 10) {
        return response()->json(['message' => '上限に達しています'], 409);
    }

    $article = Article::create($request->validated());

    return ArticleResource::make($article)->response()->setStatusCode(201);
}
```

```php
// NG Eloquent モデルを Http 層で触っている
// Http → Models の依存は PHPat が検出する。ルートモデルバインディングも同様
public function show(Article $article): JsonResponse
{
    return ArticleResource::make($article)->response();
}
```

```php
// NG index / store / show / update / destroy 以外のアクションを追加している
// 収まらない操作はアクションを増やさずコントローラーを分ける（PublishArticleController など）
public function publish(int $articleId): JsonResponse
{
}
```

### FormRequest

置き場所はコントローラーと同じドメインディレクトリの `Requests/`。
`App\Http\Requests\` のような技術的関心を最上位に置いたディレクトリは作らない。

```php
// OK
<?php

declare(strict_types=1);

namespace App\Http\Web\Article\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreArticleRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:1', 'max:100'],
            'body' => ['required', 'string'],
            'publishedAt' => ['nullable', 'date'],
        ];
    }
}
```

バリデーションルールは OpenAPI 仕様書の制約（`required` / `minLength` / `maxLength` / `format`）と
一致させる。仕様書で `type: [string, 'null']` になっているフィールドは `nullable`、
`required` に無いフィールドは `sometimes` で表現する。

**enum は Http 層で検証しない。** Http は `App\Models` に依存できないため `Rule::enum()` が使えない
（PHPat の `testHttpDependsOnlyOnUsecases` が落ちる）。Input DTO は生の文字列で受け、UseCase で
`tryFrom()` して不正なら `InvalidArgumentsException` を投げる。

```php
// NG ルールを | 区切りで書いている
return [
    'title' => 'required|string|max:100',
];

// NG authorize() に認可を書いている
// 認可は UseCase の責務
public function authorize(): bool
{
    return $this->user()->isAuthor();
}
```

### Resource

Resource は Output DTO を受け取る。Eloquent モデルを受け取らない。

```php
// OK
<?php

declare(strict_types=1);

namespace App\Http\Web\Article\Resources;

use App\UseCases\Article\Dto\ArticleOutput;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read ArticleOutput $resource
 */
class ArticleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'title' => $this->resource->title,
            'body' => $this->resource->body,
            'status' => $this->resource->statusValue(),
            'publishedAt' => $this->resource->publishedAt?->toIso8601String(),
        ];
    }
}
```

レスポンスのキーは仕様書に合わせて camelCase。DB のカラム名（snake_case）をそのまま出さない。

`$this->resource->status->value` と書かない。PHPat は `@property-read` 経由の型を追跡しないため
検出されないが、Http が `App\Models` の enum に依存することになる。

```php
// NG Eloquent モデルをそのまま Resource に渡している（Http → Models 依存になる）
class ArticleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'published_at' => $this->resource->published_at,
        ];
    }
}
```

Admin と Web で Request / Resource を共有しない。同じ形でもそれぞれの配下に定義する
（PHPat の `HttpTest` が相互参照を禁止している）。

---

## UseCase 層

### DTO

`Dto/` に置き、`readonly` にする（PHPat の `test_dtos_are_readonly` が検査する）。

```php
// OK 入力
<?php

declare(strict_types=1);

namespace App\UseCases\Article\Dto;

use Illuminate\Support\Carbon;

readonly class CreateArticleInput
{
    public function __construct(
        public int $authorId,
        public string $title,
        public string $body,
        public ?Carbon $publishedAt,
    ) {}
}
```

```php
// OK 出力
// enum はそのまま持つ（UseCases → Models は許可）。
// Http は Models の型に触れないので、backing 値はアクセサで渡す
readonly class ArticleOutput
{
    public function __construct(
        public int $id,
        public string $title,
        public string $body,
        public ArticleStatus $status,
        public ?Carbon $publishedAt,
    ) {}

    public function statusValue(): string
    {
        return $this->status->value;
    }
}

// OK 一覧の出力（要素型を docblock で明示する）
readonly class ListArticlesOutput
{
    /**
     * @param  list<ArticleSummaryOutput>  $articles
     */
    public function __construct(
        public array $articles,
    ) {}
}
```

```php
// NG Eloquent モデルを DTO に持たせている
readonly class ArticleOutput
{
    public function __construct(
        public Article $article,
    ) {}
}

// NG 出力に配列をそのまま詰めて型を失っている
readonly class ArticleOutput
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(
        public array $attributes,
    ) {}
}
```

### `__invoke`

1クラス1ユースケース。公開する実行メソッドは `__invoke` のみ
（PHPat の `test_usecases_expose_only_invoke` が検査する）。

```php
// OK
<?php

declare(strict_types=1);

namespace App\UseCases\Article;

use App\Models\Article;
use App\UseCases\Article\Dto\ArticleOutput;
use App\UseCases\Article\Dto\CreateArticleInput;

class CreateArticleUseCase
{
    public function __invoke(CreateArticleInput $input): ArticleOutput
    {
        $article = Article::create([
            'author_id' => $input->authorId,
            'title' => $input->title,
            'body' => $input->body,
            'published_at' => $input->publishedAt,
        ]);

        return new ArticleOutput(
            id: $article->id,
            title: $article->title,
            body: $article->body,
            status: $article->status,
            publishedAt: $article->published_at,
        );
    }
}
```

```php
// NG 公開メソッドを複数持っている（handle / execute の併設、public なヘルパー）
class CreateArticleUseCase
{
    public function __invoke(CreateArticleInput $input): ArticleOutput {}

    public function toOutput(Article $article): ArticleOutput {}
}

// NG Eloquent モデルを外に出している
public function __invoke(CreateArticleInput $input): Article
{
    return Article::create([...]);
}

// NG HTTP 層の型・ヘルパーを持ち込んでいる
public function __invoke(StoreArticleRequest $request): JsonResponse
{
    $authorId = request()->user()->id;
}

// NG 他の UseCase を呼んでいる
public function __invoke(CreateArticleInput $input): ArticleOutput
{
    ($this->notifyUseCase)(new NotifyInput(...));
}
```

### 認可

`__invoke` の冒頭に早期 return で書く。UseCase を読めば誰が実行できるかが分かる状態を保つ。
Policy と Gate は使わない（PHPat が `Gate` / `Illuminate\Auth\Access` への依存を禁止している）。

```php
// OK 認可はユースケースの冒頭
public function __invoke(PublishArticleInput $input): ArticleOutput
{
    $author = Author::find($input->actorId);

    if ($author === null || ! $author->canPublish()) {
        throw new PermissionDeniedException("author {$input->actorId} cannot publish");
    }

    // ...
}
```

```php
// NG abort() や AuthorizationException を UseCase で使っている
// HTTP ステータスへの変換は Http 層の責務
if (! $author->canPublish()) {
    abort(403);
}
```

「自分のレコードだけ」の所有チェックは認可として書かず、モデルのスコープで取得範囲を絞る。
見つからなければ 404 として扱う。

```php
// OK 取得範囲をスコープで絞り、見つからなければ未存在として扱う
$article = Article::ownedBy($input->actorId)->find($input->articleId);

if ($article === null) {
    throw new NotFoundException("article {$input->articleId} not found");
}

// NG 取得してから所有者を比較している
$article = Article::find($input->articleId);
if ($article->author_id !== $input->actorId) {
    // ...
}

// NG findOrFail を使っている
// ModelNotFoundException は ErrorResponseFactory が 404 に変換しないため 500 になる
$article = Article::ownedBy($input->actorId)->findOrFail($input->articleId);
```

### トランザクション

トランザクションは UseCase の中で張る。Http 層とモデルで開始しない。

```php
// OK
use Illuminate\Support\Facades\DB;

public function __invoke(CreateArticleInput $input): ArticleOutput
{
    $article = DB::transaction(function () use ($input): Article {
        $article = Article::create([...]);
        $article->attachTags($input->tagIds);

        return $article;
    });

    return new ArticleOutput(...);
}
```

複数の書き込みが1つの業務操作として成立する場合のみ張る。単一の `create` / `update` には張らない。

---

## Models 層

Repository 層は作らない。UseCase から Eloquent モデルを直接使う。
ドメインごとのディレクトリを作らず `Models/` 直下にフラットに置く。

```php
// OK 条件付きの取得はスコープとして定義する
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['author_id', 'title', 'body', 'status', 'published_at'])]
class Article extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ArticleStatus::class,
            'published_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<Article>  $query
     */
    #[Scope]
    protected function ownedBy(Builder $query, int $authorId): void
    {
        $query->where('author_id', $authorId);
    }

    /**
     * @param  Builder<Article>  $query
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->whereNotNull('published_at');
    }
}
```

```php
// NG クエリのビルドを UseCase に書いている
public function __invoke(ListArticlesInput $input): ListArticlesOutput
{
    $articles = Article::query()
        ->where('author_id', $input->authorId)
        ->whereNotNull('published_at')
        ->orderBy('published_at', 'desc')
        ->get();
}

// OK スコープを名前で呼ぶ
$articles = Article::ownedBy($input->authorId)->published()->latest('published_at')->get();
```

状態や種別は backed enum で表現する。backing 値は OpenAPI 仕様書の `enum` と一致させる。

```php
// OK
<?php

declare(strict_types=1);

namespace App\Models;

enum ArticleStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
}

// NG マジックストリングを直接扱っている
$article->status = 'published';
```

---

## Externals 層

外部サービス1つにつきディレクトリを1つ作り、interface・HTTP 実装・DTO の3点を置く。

```
app/Externals/Slack/
  SlackClient.php       # interface
  HttpSlackClient.php   # HTTP 実装
  Dto/
    SlackMessage.php
```

```php
// OK interface は 〜Client
<?php

declare(strict_types=1);

namespace App\Externals\Slack;

use App\Externals\Slack\Dto\SlackMessage;

interface SlackClient
{
    public function postMessage(SlackMessage $message): void;
}
```

```php
// OK HTTP 実装は Http〜Client。設定値と応答形式の解釈を中に閉じる
<?php

declare(strict_types=1);

namespace App\Externals\Slack;

use App\Externals\Slack\Dto\SlackMessage;
use Illuminate\Support\Facades\Http;

class HttpSlackClient implements SlackClient
{
    public function postMessage(SlackMessage $message): void
    {
        Http::withToken(config()->string('services.slack.token'))
            ->timeout(5)
            ->post('https://slack.com/api/chat.postMessage', [
                'channel' => $message->channel,
                'text' => $message->text,
            ])
            ->throw();
    }
}
```

```php
// NG UseCase が実装クラスに依存している
public function __construct(private HttpSlackClient $slack) {}

// OK interface のみに依存する
public function __construct(private SlackClient $slack) {}
```

```php
// NG 外部サービスのレスポンス構造を呼び出し側に漏らしている
public function findChannel(string $name): array
{
    return Http::get(...)->json('channels');
}

// OK パース結果を DTO に変換して返す
public function findChannel(string $name): ?SlackChannel
{
}
```

```php
// NG 呼び出し側で env / ベース URL を組み立てている
$this->slack->postMessage(env('SLACK_TOKEN'), $message);
```

### DI 登録

interface と実装の紐付けは専用の ServiceProvider に集約する。`AppServiceProvider` に書かない
（PHPat の `test_service_container_bindings_are_not_registered_in_app_service_provider` が検査する）。

```php
// OK
<?php

declare(strict_types=1);

namespace App\Providers;

use App\Externals\Slack\HttpSlackClient;
use App\Externals\Slack\SlackClient;
use Illuminate\Support\ServiceProvider;

class ExternalServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SlackClient::class, HttpSlackClient::class);
    }
}
```

新しく ServiceProvider を作った場合は `bootstrap/providers.php` に登録する。

---

## Exceptions 層

業務例外は `app/Exceptions/` に置き、`ApplicationException` を継承する。
例外クラスが持つのは `ErrorCode` だけで、HTTP ステータスは知らない。
ステータスへの変換は `app/Http/Errors/ErrorResponseFactory.php` の1箇所に閉じている。

| 例外クラス | ErrorCode | ステータス |
| --- | --- | --- |
| `InvalidArgumentsException` | `INVALID_ARGUMENTS` | 400 |
| `PermissionDeniedException` | `PERMISSION_DENIED` | 403 |
| `NotFoundException` | `NOT_FOUND` | 404 |
| `ConflictException` | `CONFLICT` | 409 |
| （`App\Exceptions` 外の例外すべて） | `INTERNAL_ERROR` | 500 |

`ApplicationException` のコンストラクタは `(string $message, ?string $clientMessage, ?Throwable $previous)`。
第1引数はログ用の内部詳細でレスポンスには出ない。クライアントに返す文言は
`ErrorCode::defaultMessage()` が既定で、`clientMessage` を渡したときだけ上書きされる。

```php
// OK 未存在
if ($article === null) {
    throw new NotFoundException("article {$input->articleId} not found");
}

// OK 認可失敗
if (! $author->canPublish()) {
    throw new PermissionDeniedException("author {$author->id} cannot publish");
}

// OK 状態競合。クライアントに固有の文言を返す
if ($article->isPublished()) {
    throw new ConflictException(
        "article {$article->id} is already published",
        clientMessage: '公開済みの記事は編集できません。',
    );
}

// OK 外部サービスのエラーをラップする。元の例外は previous に渡す
try {
    $this->slack->postMessage($message);
} catch (ConnectionException $e) {
    throw new ConflictException('slack is unavailable', previous: $e);
}
```

```php
// NG 内部詳細をクライアントに返している
throw new NotFoundException(clientMessage: $e->getMessage());

// NG 例外クラスに HTTP ステータスを持たせている
class ArticleLockedException extends ApplicationException
{
    public function status(): int
    {
        return 423;
    }
}

// NG Http 層でステータスを組み立てている
if ($article === null) {
    return response()->json(['code' => 'NOT_FOUND', 'message' => '記事が見つかりません'], 404);
}

// NG 想定外の例外を業務例外でラップしている
// App\Exceptions 外の例外は 500 INTERNAL_ERROR になるので、握らずそのまま上げる
try {
    $article->save();
} catch (QueryException $e) {
    throw new ConflictException('save failed', previous: $e);
}
```

新しい `ErrorCode` が必要になった場合は、追加箇所が4つある。

1. `app/Exceptions/ErrorCode.php` — case と `defaultMessage()`
2. `app/Exceptions/{Name}Exception.php` — `errorCode()` を返すクラス
3. `app/Http/Errors/ErrorResponseFactory.php` — `status()` の match
4. `docs/api/shared/error.yml` — `ErrorCode` enum と `responses`

投げる 4xx は OpenAPI 仕様書の `responses` に定義されている必要がある。
定義が無いステータスを返すと契約テスト（`assertValidResponse()`）が落ちる。

---

## ルーティング

`routes/api.php` にロジックを書かない。tuple 記法で定義し、ルート名は camelCase。
URL とルートパラメータ名は OpenAPI 仕様書の定義に一致させる。

```php
// OK
<?php

declare(strict_types=1);

use App\Http\Web\Article\ArticleController;
use Illuminate\Support\Facades\Route;

Route::get('/articles', [ArticleController::class, 'index'])->name('articles.index');
Route::post('/articles', [ArticleController::class, 'store'])->name('articles.store');
```

```php
// NG 文字列記法
Route::get('/articles', 'ArticleController@index');

// NG クロージャに処理を書いている
Route::get('/articles', function (Request $request) {
    return Article::all();
});

// NG 仕様書のパスパラメータ名と違う（仕様書は {articleId}）
Route::get('/articles/{id}', [ArticleController::class, 'show']);
```

admin 仕様書のエンドポイントは `/admin/` を先頭に付け、`App\Http\Admin\` のコントローラーに向ける。
