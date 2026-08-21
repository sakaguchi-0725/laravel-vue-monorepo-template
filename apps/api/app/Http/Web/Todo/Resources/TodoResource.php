<?php

declare(strict_types=1);

namespace App\Http\Web\Todo\Resources;

use App\UseCases\Todo\Dto\TodoOutput;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read TodoOutput $resource
 */
class TodoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'title' => $this->resource->title,
            'description' => $this->resource->description,
            'status' => $this->resource->statusValue(),
            'dueOn' => $this->resource->dueOn,
        ];
    }
}
