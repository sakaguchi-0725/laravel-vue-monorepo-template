<?php

declare(strict_types=1);

namespace App\UseCases\Todo\Dto;

readonly class ListTodosInput
{
    public function __construct(
        public ?string $status,
    ) {}
}
