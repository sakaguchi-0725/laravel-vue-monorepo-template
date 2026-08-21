<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Todo;
use App\Models\TodoStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Todo>
 */
class TodoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'status' => TodoStatus::Pending,
            'due_on' => null,
        ];
    }

    public function done(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => TodoStatus::Done,
        ]);
    }
}
