<?php

declare(strict_types=1);

namespace App\UseCases\Todo;

use App\Exceptions\InvalidArgumentsException;
use App\Models\Todo;
use App\Models\TodoStatus;
use App\UseCases\Todo\Dto\ListTodosInput;
use App\UseCases\Todo\Dto\ListTodosOutput;
use App\UseCases\Todo\Dto\TodoOutput;

class ListTodosUseCase
{
    public function __invoke(ListTodosInput $input): ListTodosOutput
    {
        $query = Todo::query()->ordered();

        if ($input->status !== null) {
            $query->withStatus($this->toStatus($input->status));
        }

        $todos = $query->get()
            ->map(fn (Todo $todo): TodoOutput => TodoOutput::fromModel($todo))
            ->all();

        return new ListTodosOutput(array_values($todos));
    }

    private function toStatus(string $status): TodoStatus
    {
        $resolved = TodoStatus::tryFrom($status);

        if ($resolved === null) {
            throw new InvalidArgumentsException(
                "unknown status {$status}",
                clientMessage: 'ステータスの値が正しくありません。',
            );
        }

        return $resolved;
    }
}
