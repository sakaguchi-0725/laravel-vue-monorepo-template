# コーディングルールのサンプルコード

`.claude/rules/backend/php.md` のルールに則ったコード例。
`mise -C apps/api run format`（Pint）では直らず、人が書き分けるものを中心に示す。

## 型

PHPStan は `level: max`。

```php
// OK 引数・戻り値・プロパティすべてに型宣言
public function findByAuthor(int $authorId, ?ArticleStatus $status): ArticleOutput
{
}

// NG mixed を使っている
public function handle(mixed $payload): mixed
{
}

// OK 型が定まらない場合はユニオン型か具体型に絞る
public function handle(string|int $identifier): ArticleOutput
{
}
```

```php
// OK docblock は配列の要素型を表すときだけ
/**
 * @param  list<int>  $tagIds
 * @return array<string, string>
 */
public function attachTags(array $tagIds): array
{
}

// NG 型宣言と重複する情報を docblock に書いている
/**
 * @param  int  $authorId
 * @param  ArticleStatus|null  $status
 * @return ArticleOutput
 */
public function findByAuthor(int $authorId, ?ArticleStatus $status): ArticleOutput
{
}
```

```php
// OK nullable は ?T
public ?Carbon $publishedAt;

// NG
public Carbon|null $publishedAt;
```

`CarbonImmutable` は `Carbon\CarbonImmutable`。`Illuminate\Support\CarbonImmutable` は存在しない
（`Illuminate\Support` にあるのは `Carbon` だけ）。

## 比較

```php
// NG empty() は 0 も '' も [] も null も真になる。意図が読めない
if (empty($input->title)) {
}

// OK 意図を明示する
if ($input->title === '') {
}
if ($input->publishedAt === null) {
}
if ($input->tagIds === []) {
}
if ($input->viewCount === 0) {
}
```

```php
// NG ショートテナリーは 0 や '' も右辺に落ちる
$title = $input->title ?: 'no title';

// OK null だけを扱うなら ??
$title = $input->title ?? 'no title';

// OK 条件を明示するなら完全な三項演算子
$title = $input->title === '' ? 'no title' : $input->title;
```

```php
// NG 第3引数がないと '1' == 1 のような緩い比較になる
if (in_array($status, $allowed)) {
}

// OK
if (in_array($status, $allowed, true)) {
}
```

## 制御構文

異常系を先に処理し、正常系をインデントなしで末尾に置く。

```php
// OK 早期 return
public function __invoke(PublishArticleInput $input): ArticleOutput
{
    $article = Article::ownedBy($input->actorId)->find($input->articleId);

    if ($article === null) {
        throw new NotFoundException("article {$input->articleId} not found");
    }

    $article->publish();

    return $this->toOutput($article);
}

// NG else でネストしている
if ($article !== null) {
    $article->publish();

    return $this->toOutput($article);
} else {
    // ...
}
```

## クラス

```php
// OK constructor property promotion
class CreateArticleUseCase
{
    public function __construct(private SlackClient $slack) {}
}

// NG プロパティ宣言と代入を分けている
class CreateArticleUseCase
{
    private SlackClient $slack;

    public function __construct(SlackClient $slack)
    {
        $this->slack = $slack;
    }
}

// NG 引数なしの空コンストラクタを残している
class ListArticlesUseCase
{
    public function __construct() {}
}
```

```php
// OK DTO と値オブジェクトは readonly
readonly class ArticleOutput
{
    public function __construct(public int $id) {}
}

// NG UseCase / Controller / Model に readonly や final をデフォルトで付けている
final readonly class CreateArticleUseCase
{
}
```

```php
// OK trait は1行1トレイト
use HasApiTokens;
use HasFactory;

// NG
use HasApiTokens, HasFactory;
```

## 値の表現

```php
// NG マジックストリング・マジックナンバー
if ($article->status === 'published') {
}
if ($author->plan === 2) {
}

// OK backed enum。ケースは PascalCase、backing 値は OpenAPI 仕様書の enum と一致させる
enum ArticleStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
}

if ($article->status === ArticleStatus::Published) {
}
```

## 命名

```php
// OK クラス名は役割の suffix で固定。コントローラは単数形
ArticleController / StoreArticleRequest / ArticleResource / CreateArticleUseCase

// NG
ArticlesController / ArticleValidator / ArticleTransformer / CreateArticleService
```

```php
// OK イベントは時制で表す
ArticlePublished   // 発生後
PublishingArticle  // 発生前

// NG
ArticlePublishEvent
```

## 設定値

```php
// NG config/ の外で env() を呼んでいる。設定キャッシュ後に null になる
$token = env('SLACK_TOKEN');

// OK config/services.php に定義し、config() で読む
$token = config()->string('services.slack.token');
```

config のファイル名はケバブケース、キーは snake_case。それ以外の公開されない文字列は camelCase。

## コメント

**コメントは書かない。これを既定とする。** 迷ったら書かない。

```php
// NG すべて What コメント。コードを読めば分かる内容の重複
class CreateArticleUseCase
{
    // 記事を作成する
    public function __invoke(CreateArticleInput $input): ArticleOutput
    {
        // トランザクションを開始
        return DB::transaction(function () use ($input): ArticleOutput {
            // 記事を保存
            $article = Article::create([...]);

            // ---- レスポンスの組み立て ----
            return new ArticleOutput(...);
        });
    }
}
```

```php
// OK コードから導けない業務上の制約
// 掲載期限は契約上「公開日から90日」で固定。プランによる差はない
private const int VISIBLE_DAYS = 90;

// OK 一見不要に見える処理を残している理由
// Slack の API はリトライ時に同一 ts を返さないため、送信済み判定は自前のキーで行う
$key = $message->idempotencyKey;
```

PHPDoc も同じ基準。型情報の再掲は書かない（Pint の `no_superfluous_phpdoc_tags` が削除する）。
