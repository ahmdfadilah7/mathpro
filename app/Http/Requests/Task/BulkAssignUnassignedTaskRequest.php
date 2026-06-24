<?php

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;

class BulkAssignUnassignedTaskRequest extends FormRequest
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
            'assignee_id' => ['required', 'integer'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'task_ids' => 'task',
            'task_ids.*' => 'task',
            'assignee_id' => 'assignee',
        ];
    }
}
