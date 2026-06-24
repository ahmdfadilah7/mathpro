<?php

namespace App\Http\Requests\Task;

use App\Models\Task;
use App\Services\ProjectAccessService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignUnassignedTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Task $task */
        $task = $this->route('task');
        $task->loadMissing('project');

        if ($task->assignee_id !== null || ! $task->project) {
            return false;
        }

        return app(ProjectAccessService::class)->canAssignTask(
            $this->user(),
            $task->project
        );
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var Task $task */
        $task = $this->route('task');
        $assignable = app(ProjectAccessService::class)->assignableUserIds($task->project);

        return [
            'assignee_id' => [
                'required',
                'integer',
                Rule::in($assignable),
            ],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'assignee_id' => 'assignee',
        ];
    }
}
