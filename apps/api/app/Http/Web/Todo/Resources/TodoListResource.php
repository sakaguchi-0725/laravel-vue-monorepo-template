<?php

declare(strict_types=1);

namespace App\Http\Web\Todo\Resources;

use App\UseCases\Todo\Dto\ListTodosOutput;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read ListTodosOutput $resource
 */
class TodoListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'todos' => TodoResource::collection($this->resource->todos),
        ];
    }
}
