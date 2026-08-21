<?php

declare(strict_types=1);

namespace App\UseCases\Todo;

use App\Exceptions\InvalidArgumentsException;
use App\Models\Todo;
use App\Models\TodoStatus;
use App\UseCases\Todo\Dto\CreateTodoInput;
use App\UseCases\Todo\Dto\TodoOutput;
use Carbon\CarbonImmutable;

class CreateTodoUseCase
{
    private const string TIMEZONE = 'Asia/Tokyo';

    public function __invoke(CreateTodoInput $input): TodoOutput
    {
        if ($input->dueOn !== null && $this->isPast($input->dueOn)) {
            throw new InvalidArgumentsException(
                "due_on {$input->dueOn} is in the past",
                clientMessage: '期限日に過去の日付は指定できません。',
            );
        }

        $todo = Todo::create([
            'title' => $input->title,
            'description' => $input->description,
            'status' => TodoStatus::Pending,
            'due_on' => $input->dueOn,
        ]);

        return TodoOutput::fromModel($todo);
    }

    private function isPast(string $dueOn): bool
    {
        return CarbonImmutable::parse($dueOn, self::TIMEZONE)
            ->startOfDay()
            ->isBefore(CarbonImmutable::now(self::TIMEZONE)->startOfDay());
    }
}
