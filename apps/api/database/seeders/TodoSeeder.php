<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Todo;
use App\Models\TodoStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class TodoSeeder extends Seeder
{
    public function run(): void
    {
        $today = Carbon::today();

        Todo::factory()->create([
            'title' => '請求書を送付する',
            'description' => '今月分の請求書をPDFにして送る。',
            'status' => TodoStatus::Pending,
            'due_on' => $today->copy()->addDays(3),
        ]);

        Todo::factory()->create([
            'title' => '議事録をまとめる',
            'description' => null,
            'status' => TodoStatus::Pending,
            'due_on' => $today->copy()->addDay(),
        ]);

        Todo::factory()->create([
            'title' => '契約書をレビューする',
            'description' => "先方から届いた改定版を確認する。\n修正案は今週中に返す。",
            'status' => TodoStatus::Pending,
            'due_on' => $today->copy()->addDays(14),
        ]);

        Todo::factory()->create([
            'title' => '名刺を発注する',
            'description' => '残り20枚。100枚単位で追加発注する。',
            'status' => TodoStatus::Pending,
            'due_on' => null,
        ]);

        Todo::factory()->create([
            'title' => '備品を棚卸しする',
            'description' => null,
            'status' => TodoStatus::Pending,
            'due_on' => null,
        ]);

        Todo::factory()->create([
            'title' => '交通費を精算する',
            'description' => '先月分の領収書を経費システムに登録する。',
            'status' => TodoStatus::Done,
            'due_on' => $today->copy()->subDays(3),
        ]);

        Todo::factory()->create([
            'title' => '週次レポートを提出する',
            'description' => null,
            'status' => TodoStatus::Done,
            'due_on' => $today->copy()->subDay(),
        ]);
    }
}
