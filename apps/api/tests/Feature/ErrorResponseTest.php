<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exceptions\ConflictException;
use App\Exceptions\InvalidArgumentsException;
use App\Exceptions\NotFoundException;
use App\Exceptions\PermissionDeniedException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;

class ErrorResponseTest extends WebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('api')->prefix('api')->group(function (): void {
            Route::get('/_errors/invalid-arguments', fn () => throw new InvalidArgumentsException('name is blank'));
            Route::get('/_errors/permission-denied', fn () => throw new PermissionDeniedException);
            Route::get('/_errors/not-found', fn () => throw new NotFoundException);
            Route::get('/_errors/conflict', fn () => throw new ConflictException);
            Route::get('/_errors/client-message', fn () => throw new NotFoundException(
                'article 1 not found',
                clientMessage: '記事が見つかりません。',
            ));
            Route::get('/_errors/validation', fn (Request $request) => $request->validate([
                'name' => ['required', 'string', 'max:50'],
            ]));
            Route::get('/_errors/unexpected', fn () => throw new RuntimeException('DB credentials are invalid'));
            Route::get('/_errors/model-not-found', fn () => throw new ModelNotFoundException);
            Route::get('/_errors/service-unavailable', fn () => abort(503));
            Route::get('/_errors/authorization', fn () => throw new AuthorizationException('policy denied'));
            Route::get('/_errors/token-mismatch', fn () => throw new TokenMismatchException);
        });
    }

    #[Test]
    public function 引数不正の業務例外が400で返ること(): void
    {
        $response = $this->getJson('/api/_errors/invalid-arguments');

        $response->assertStatus(400);
        $this->assertSame('INVALID_ARGUMENTS', $response->json('code'));
    }

    #[Test]
    public function 権限なしの業務例外が403で返ること(): void
    {
        $response = $this->getJson('/api/_errors/permission-denied');

        $response->assertStatus(403);
        $this->assertSame('PERMISSION_DENIED', $response->json('code'));
    }

    #[Test]
    public function 未存在の業務例外が404で返ること(): void
    {
        $response = $this->getJson('/api/_errors/not-found');

        $response->assertStatus(404);
        $this->assertSame('NOT_FOUND', $response->json('code'));
    }

    #[Test]
    public function 状態競合の業務例外が409で返ること(): void
    {
        $response = $this->getJson('/api/_errors/conflict');

        $response->assertStatus(409);
        $this->assertSame('CONFLICT', $response->json('code'));
    }

    #[Test]
    public function クライアント向け文言を渡さない場合はエラーコードの既定文言が返ること(): void
    {
        $response = $this->getJson('/api/_errors/not-found');

        $this->assertSame('対象が見つかりません。', $response->json('message'));
    }

    #[Test]
    public function クライアント向け文言を渡した場合はそちらが返り内部メッセージは出ないこと(): void
    {
        $response = $this->getJson('/api/_errors/client-message');

        $response->assertStatus(404);
        $this->assertSame('記事が見つかりません。', $response->json('message'));
        $response->assertDontSee('article 1 not found');
    }

    #[Test]
    public function バリデーション失敗が400で返りerrorsが付かないこと(): void
    {
        $response = $this->getJson('/api/_errors/validation');

        $response->assertStatus(400);
        $this->assertSame('INVALID_ARGUMENTS', $response->json('code'));
        $this->assertNull($response->json('errors'));
    }

    #[Test]
    public function ルート未定義が404で返ること(): void
    {
        $response = $this->getJson('/api/_errors/undefined-path');

        $response->assertStatus(404);
        $this->assertSame('NOT_FOUND', $response->json('code'));
    }

    #[Test]
    public function 想定外の例外が500で返り例外メッセージが出ないこと(): void
    {
        config(['app.debug' => false]);

        $response = $this->getJson('/api/_errors/unexpected');

        $response->assertStatus(500);
        $this->assertSame('INTERNAL_ERROR', $response->json('code'));
        $response->assertDontSee('DB credentials are invalid');
    }

    #[Test]
    public function 許可されないメソッドが405で返ること(): void
    {
        config(['app.debug' => false]);

        $response = $this->postJson('/api/_errors/not-found');

        $response->assertStatus(405);
        $this->assertSame('INVALID_ARGUMENTS', $response->json('code'));
    }

    #[Test]
    public function abortで指定したステータスが維持されること(): void
    {
        config(['app.debug' => false]);

        $response = $this->getJson('/api/_errors/service-unavailable');

        $response->assertStatus(503);
        $this->assertSame('INTERNAL_ERROR', $response->json('code'));
    }

    #[Test]
    public function 認可の標準例外が403で返ること(): void
    {
        config(['app.debug' => false]);

        $response = $this->getJson('/api/_errors/authorization');

        $response->assertStatus(403);
        $this->assertSame('PERMISSION_DENIED', $response->json('code'));
        $response->assertDontSee('policy denied');
    }

    #[Test]
    public function トークン不一致の標準例外が419で返ること(): void
    {
        config(['app.debug' => false]);

        $response = $this->getJson('/api/_errors/token-mismatch');

        $response->assertStatus(419);
        $this->assertSame('INVALID_ARGUMENTS', $response->json('code'));
    }

    #[Test]
    public function モデル未存在の例外が404ではなく500で返ること(): void
    {
        config(['app.debug' => false]);

        $response = $this->getJson('/api/_errors/model-not-found');

        $response->assertStatus(500);
        $this->assertSame('INTERNAL_ERROR', $response->json('code'));
    }
}
