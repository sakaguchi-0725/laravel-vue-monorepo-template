<?php

declare(strict_types=1);

namespace App\UseCases\Todo\Dto;

readonly class CreateTodoInput
{
    public function __construct(
        public string $title,
        public ?string $description,
        public ?string $dueOn,
    ) {}
}
