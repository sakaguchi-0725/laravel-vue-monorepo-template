<?php

declare(strict_types=1);

use App\Http\Admin\Todo\TodoController as AdminTodoController;
use App\Http\Web\Todo\TodoController as WebTodoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/todos', [WebTodoController::class, 'index'])->name('todos.index');
Route::post('/todos', [WebTodoController::class, 'store'])->name('todos.store');

Route::get('/admin/todos', [AdminTodoController::class, 'index'])->name('adminTodos.index');
Route::post('/admin/todos', [AdminTodoController::class, 'store'])->name('adminTodos.store');
