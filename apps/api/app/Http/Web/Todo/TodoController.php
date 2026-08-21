<?php

declare(strict_types=1);

namespace App\Http\Web\Todo;

use App\Http\Web\Todo\Requests\ListTodosRequest;
use App\Http\Web\Todo\Requests\StoreTodoRequest;
use App\Http\Web\Todo\Resources\TodoListResource;
use App\Http\Web\Todo\Resources\TodoResource;
use App\UseCases\Todo\CreateTodoUseCase;
use App\UseCases\Todo\Dto\CreateTodoInput;
use App\UseCases\Todo\Dto\ListTodosInput;
use App\UseCases\Todo\ListTodosUseCase;
use Illuminate\Http\JsonResponse;

class TodoController
{
    public function index(ListTodosRequest $request, ListTodosUseCase $useCase): JsonResponse
    {
        $input = new ListTodosInput(
            status: $request->filled('status') ? $request->string('status')->toString() : null,
        );

        return TodoListResource::make($useCase($input))->response();
    }

    public function store(StoreTodoRequest $request, CreateTodoUseCase $useCase): JsonResponse
    {
        $input = new CreateTodoInput(
            title: $request->string('title')->toString(),
            description: $request->filled('description') ? $request->string('description')->toString() : null,
            dueOn: $request->filled('dueOn') ? $request->string('dueOn')->toString() : null,
        );

        return TodoResource::make($useCase($input))
            ->response()
            ->setStatusCode(201);
    }
}
