<?php

declare(strict_types=1);

namespace Tests\Feature\Web\Todo;

use App\Models\Todo;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\WebTestCase;

class ListTodosTest extends WebTestCase
{
    #[Test]
    public function 利用者がタスク一覧を取得できること(): void
    {
        Todo::factory()->create(['title' => '後のタスク', 'due_on' => '2026-09-30']);
        Todo::factory()->create(['title' => '先のタスク', 'due_on' => '2026-09-01']);

        $response = $this->getJson('/api/todos');

        $response->assertValidRequest()
            ->assertValidResponse(200);

        $this->assertSame(['先のタスク', '後のタスク'], array_column($response->json('todos'), 'title'));
        $this->assertSame(
            ['id', 'title', 'description', 'status', 'dueOn'],
            array_keys($response->json('todos.0')),
        );
    }

    #[Test]
    public function 期限日が未設定のタスクが含まれる場合、期限日ありのタスクより後に並ぶこと(): void
    {
        Todo::factory()->create(['title' => '期限日なし', 'due_on' => null]);
        Todo::factory()->create(['title' => '期限日あり', 'due_on' => '2026-09-30']);

        $response = $this->getJson('/api/todos');

        $response->assertValidResponse(200);
        $this->assertSame(['期限日あり', '期限日なし'], array_column($response->json('todos'), 'title'));
    }

    #[Test]
    public function ステータスを指定した場合、そのステータスのタスクだけが返ること(): void
    {
        Todo::factory()->create(['title' => '未完了のタスク']);
        Todo::factory()->done()->create(['title' => '完了済みのタスク']);

        $response = $this->getJson('/api/todos?status=pending');

        $response->assertValidRequest()
            ->assertValidResponse(200);

        $this->assertSame(['未完了のタスク'], array_column($response->json('todos'), 'title'));
    }

    #[Test]
    public function タスクが1件も存在しない場合、空の一覧が返ること(): void
    {
        $response = $this->getJson('/api/todos');

        $response->assertValidResponse(200);
        $this->assertSame([], $response->json('todos'));
    }

    #[Test]
    public function 未定義のステータスを指定した場合、取得が拒否されること(): void
    {
        Todo::factory()->create();

        $response = $this->getJson('/api/todos?status=archived');

        $response->assertValidResponse(400);
        $this->assertSame('INVALID_ARGUMENTS', $response->json('code'));
        $this->assertSame('ステータスの値が正しくありません。', $response->json('message'));
    }

    #[Test]
    public function 期限日が同じ場合、_i_dの昇順で並ぶこと(): void
    {
        $first = Todo::factory()->create(['title' => '先に登録', 'due_on' => '2026-09-30']);
        $second = Todo::factory()->create(['title' => '後に登録', 'due_on' => '2026-09-30']);

        $response = $this->getJson('/api/todos');

        $response->assertValidResponse(200);
        $this->assertSame([$first->id, $second->id], array_column($response->json('todos'), 'id'));
    }
}
