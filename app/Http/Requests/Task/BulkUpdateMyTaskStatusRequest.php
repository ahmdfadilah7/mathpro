<?php

namespace App\Http\Requests\Task;

use App\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkUpdateMyTaskStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'task_ids' => ['required', 'array', 'min:1', 'max:100'],
            'task_ids.*' => ['required', 'integer', 'distinct'],
            'status' => ['required', Rule::enum(TaskStatus::class)],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'task_ids' => 'task',
            'task_ids.*' => 'task',
            'status' => 'status',
        ];
    }
}
