<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Todo;

use App\Models\Todo;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\AdminTestCase;

class ListTodosTest extends AdminTestCase
{
    #[Test]
    public function 管理者がタスク一覧を取得できること(): void
    {
        Todo::factory()->create(['title' => '後のタスク', 'due_on' => '2026-09-30']);
        Todo::factory()->create(['title' => '先のタスク', 'due_on' => '2026-09-01']);

        $response = $this->getJson('/api/admin/todos');

        $response->assertValidRequest()
            ->assertValidResponse(200);

        $this->assertSame(['先のタスク', '後のタスク'], array_column($response->json('todos'), 'title'));
    }

    #[Test]
    public function 未定義のステータスを指定した場合、取得が拒否されること(): void
    {
        Todo::factory()->create();

        $response = $this->getJson('/api/admin/todos?status=archived');

        $response->assertValidResponse(400);
        $this->assertSame('INVALID_ARGUMENTS', $response->json('code'));
    }
}
