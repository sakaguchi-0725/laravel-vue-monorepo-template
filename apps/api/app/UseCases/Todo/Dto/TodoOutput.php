<?php

declare(strict_types=1);

namespace App\UseCases\Todo\Dto;

use App\Models\Todo;
use App\Models\TodoStatus;

readonly class TodoOutput
{
    public function __construct(
        public int $id,
        public string $title,
        public ?string $description,
        public TodoStatus $status,
        public ?string $dueOn,
    ) {}

    public static function fromModel(Todo $todo): self
    {
        return new self(
            id: $todo->id,
            title: $todo->title,
            description: $todo->description,
            status: $todo->status,
            dueOn: $todo->due_on?->toDateString(),
        );
    }

    public function statusValue(): string
    {
        return $this->status->value;
    }
}
