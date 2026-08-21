<?php

declare(strict_types=1);

namespace Tests\Feature\Web\Todo;

use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\WebTestCase;

class CreateTodoTest extends WebTestCase
{
    #[Test]
    public function 利用者がタスクを作成できること(): void
    {
        $response = $this->postJson('/api/todos', [
            'title' => '請求書を送付する',
            'description' => '今月分の請求書をPDFにして送る。',
            'dueOn' => '2026-09-30',
        ]);

        $response->assertValidRequest()
            ->assertValidResponse(201);

        $this->assertIsInt($response->json('id'));
        $this->assertSame('請求書を送付する', $response->json('title'));
        $this->assertSame('今月分の請求書をPDFにして送る。', $response->json('description'));
        $this->assertSame('pending', $response->json('status'));
        $this->assertSame('2026-09-30', $response->json('dueOn'));

        $this->assertDatabaseHas('todos', [
            'title' => '請求書を送付する',
            'description' => '今月分の請求書をPDFにして送る。',
            'status' => 'pending',
            'due_on' => '2026-09-30',
        ]);
    }

    #[Test]
    public function 詳細説明と期限日を指定しない場合、いずれも未設定のタスクが作成されること(): void
    {
        $response = $this->postJson('/api/todos', ['title' => '請求書を送付する']);

        $response->assertValidRequest()
            ->assertValidResponse(201);

        $this->assertNull($response->json('description'));
        $this->assertNull($response->json('dueOn'));
        $this->assertSame('pending', $response->json('status'));

        $this->assertDatabaseHas('todos', [
            'title' => '請求書を送付する',
            'description' => null,
            'due_on' => null,
        ]);
    }

    #[Test]
    public function 期限日に過去の日付を指定した場合、作成が拒否されること(): void
    {
        $yesterday = CarbonImmutable::now('Asia/Tokyo')->subDay()->toDateString();

        $response = $this->postJson('/api/todos', [
            'title' => '請求書を送付する',
            'dueOn' => $yesterday,
        ]);

        $response->assertValidResponse(400);
        $this->assertSame('INVALID_ARGUMENTS', $response->json('code'));
        $this->assertSame('期限日に過去の日付は指定できません。', $response->json('message'));

        $this->assertDatabaseCount('todos', 0);
    }

    #[Test]
    public function 件名を指定しない場合、作成が拒否されること(): void
    {
        $response = $this->postJson('/api/todos', ['description' => '今月分の請求書をPDFにして送る。']);

        $response->assertValidResponse(400);
        $this->assertSame('INVALID_ARGUMENTS', $response->json('code'));
        $this->assertDatabaseCount('todos', 0);
    }

    #[Test]
    public function 件名が100文字を超える場合、作成が拒否されること(): void
    {
        $response = $this->postJson('/api/todos', ['title' => str_repeat('あ', 101)]);

        $response->assertValidResponse(400);
        $this->assertSame('INVALID_ARGUMENTS', $response->json('code'));
        $this->assertDatabaseCount('todos', 0);
    }

    #[Test]
    public function 期限日に当日を指定した場合、作成できること(): void
    {
        $today = CarbonImmutable::now('Asia/Tokyo')->toDateString();

        $response = $this->postJson('/api/todos', [
            'title' => '請求書を送付する',
            'dueOn' => $today,
        ]);

        $response->assertValidResponse(201);
        $this->assertSame($today, $response->json('dueOn'));
    }
}
