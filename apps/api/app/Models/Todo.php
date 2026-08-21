<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TodoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $title
 * @property string|null $description
 * @property TodoStatus $status
 * @property Carbon|null $due_on
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Database\Factories\TodoFactory factory($count = null, $state = [])
 * @method static Builder<static>|Todo newModelQuery()
 * @method static Builder<static>|Todo newQuery()
 * @method static Builder<static>|Todo ordered()
 * @method static Builder<static>|Todo query()
 * @method static Builder<static>|Todo whereCreatedAt($value)
 * @method static Builder<static>|Todo whereDescription($value)
 * @method static Builder<static>|Todo whereDueOn($value)
 * @method static Builder<static>|Todo whereId($value)
 * @method static Builder<static>|Todo whereStatus($value)
 * @method static Builder<static>|Todo whereTitle($value)
 * @method static Builder<static>|Todo whereUpdatedAt($value)
 * @method static Builder<static>|Todo withStatus(\App\Models\TodoStatus $status)
 *
 * @mixin \Eloquent
 */
#[Fillable(['title', 'description', 'status', 'due_on'])]
class Todo extends Model
{
    /** @use HasFactory<TodoFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TodoStatus::class,
            'due_on' => 'date',
        ];
    }

    /**
     * @param  Builder<Todo>  $query
     */
    #[Scope]
    protected function withStatus(Builder $query, TodoStatus $status): void
    {
        $query->where('status', $status);
    }

    /**
     * @param  Builder<Todo>  $query
     */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderByRaw('due_on IS NULL ASC, due_on ASC, id ASC');
    }
}
