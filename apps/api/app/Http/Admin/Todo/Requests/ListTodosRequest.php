<?php

declare(strict_types=1);

namespace App\Http\Admin\Todo\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListTodosRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string'],
        ];
    }
}
