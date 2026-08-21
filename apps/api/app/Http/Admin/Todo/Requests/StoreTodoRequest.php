<?php

declare(strict_types=1);

namespace App\Http\Admin\Todo\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTodoRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:1', 'max:100'],
            'description' => ['nullable', 'string'],
            'dueOn' => ['nullable', 'date_format:Y-m-d'],
        ];
    }
}
