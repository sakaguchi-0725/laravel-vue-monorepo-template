<?php

declare(strict_types=1);

namespace App\UseCases\Todo\Dto;

readonly class ListTodosOutput
{
    /**
     * @param  list<TodoOutput>  $todos
     */
    public function __construct(
        public array $todos,
    ) {}
}
