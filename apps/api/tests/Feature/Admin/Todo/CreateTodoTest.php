<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Todo;

use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\AdminTestCase;

class CreateTodoTest extends AdminTestCase
{
    #[Test]
    public function 管理者がタスクを作成できること(): void
    {
        $response = $this->postJson('/api/admin/todos', [
            'title' => '請求書を送付する',
            'description' => '今月分の請求書をPDFにして送る。',
            'dueOn' => '2026-09-30',
        ]);

        $response->assertValidRequest()
            ->assertValidResponse(201);

        $this->assertSame('pending', $response->json('status'));

        $this->assertDatabaseHas('todos', [
            'title' => '請求書を送付する',
            'status' => 'pending',
            'due_on' => '2026-09-30',
        ]);
    }
}
