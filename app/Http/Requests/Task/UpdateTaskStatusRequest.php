<?php

namespace App\Http\Requests\Task;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Services\ProjectAccessService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Task $task */
        $task = $this->route('task');
        $status = TaskStatus::tryFrom((string) $this->input('status'));

        if (! $status) {
            return false;
        }

        return app(ProjectAccessService::class)->canSetTaskStatusTo(
            $this->user(),
            $task->project,
            $task,
            $status
        );
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(TaskStatus::class)],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'status' => 'status task',
        ];
    }
}
