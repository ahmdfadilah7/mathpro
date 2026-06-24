<?php

namespace App\Http\Requests\Task;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Services\ProjectAccessService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Task $task */
        $task = $this->route('task');

        return app(ProjectAccessService::class)->canUpdateTask(
            $this->user(),
            $task->project,
            $task
        );
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'assignee_id' => $this->filled('assignee_id') ? $this->assignee_id : null,
            'description' => $this->filled('description') ? $this->description : null,
            'due_date' => $this->filled('due_date') ? $this->due_date : null,
            'start_date' => $this->filled('start_date') ? $this->start_date : null,
            'estimated_hours' => $this->filled('estimated_hours') ? $this->estimated_hours : null,
            'actual_hours' => $this->filled('actual_hours') ? $this->actual_hours : null,
        ]);
    }

    public function editMode(): string
    {
        /** @var Task $task */
        $task = $this->route('task');

        return app(ProjectAccessService::class)->getTaskEditMode(
            $this->user(),
            $task->project,
            $task
        ) ?? ProjectAccessService::EDIT_FULL;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        if ($this->editMode() === ProjectAccessService::EDIT_CONTRIBUTOR) {
            return [
                'status' => ['required', Rule::enum(TaskStatus::class)],
                'description' => ['nullable', 'string', 'max:5000'],
            ];
        }

        /** @var Task $task */
        $task = $this->route('task');
        $assignable = app(ProjectAccessService::class)->assignableUserIds($task->project);

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::enum(TaskStatus::class)],
            'priority' => ['required', Rule::enum(TaskPriority::class)],
            'assignee_id' => [
                'nullable',
                'integer',
                Rule::in($assignable),
            ],
            'due_date' => ['nullable', 'date'],
            'start_date' => ['nullable', 'date'],
            'estimated_hours' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'actual_hours' => ['nullable', 'numeric', 'min:0', 'max:9999'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return (new StoreTaskRequest)->attributes();
    }
}
