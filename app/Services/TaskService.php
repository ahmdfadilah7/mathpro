<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class TaskService
{
    public function __construct(
        private readonly ProjectAccessService $accessService,
        private readonly ActivityLogService $activityLog
    ) {}

    public function create(Project $project, array $data, User $actor): Task
    {
        return DB::transaction(function () use ($project, $data, $actor) {
            $payload = $this->preparePayload($data);
            $payload['project_id'] = $project->id;
            $payload['created_by'] = $actor->id;
            $payload['order'] = $payload['order']
                ?? ((int) $project->tasks()->max('order')) + 1;

            $task = Task::create($payload);

            $this->activityLog->log(
                $actor,
                'created',
                $task,
                "Membuat task \"{$task->title}\" di project {$project->code}",
                $this->activityLog->projectContext($project)
            );

            return $task;
        });
    }

    public function updateStatus(Task $task, string $status, User $actor): Task
    {
        return DB::transaction(function () use ($task, $status, $actor) {
            $previous = $task->status->value;
            $task->update(['status' => $status]);
            $task = $task->fresh(['assignee:id,name,avatar', 'creator:id,name,avatar', 'project:id,code,name']);

            $this->activityLog->log(
                $actor,
                'status_changed',
                $task,
                "Mengubah status task \"{$task->title}\" menjadi {$task->status->label()}",
                array_merge($this->activityLog->taskContext($task), [
                    'from_status' => $previous,
                    'to_status' => $status,
                ])
            );

            return $task;
        });
    }

    public function assignAssignee(Task $task, int $assigneeId, User $actor): Task
    {
        return DB::transaction(function () use ($task, $assigneeId, $actor) {
            $assignee = User::query()->find($assigneeId);
            $task->update(['assignee_id' => $assigneeId]);
            $task = $task->fresh(['assignee:id,name,avatar', 'creator:id,name,avatar', 'project:id,name,code,color,manager_id']);

            $this->activityLog->log(
                $actor,
                'assigned',
                $task,
                "Menetapkan {$assignee?->name} sebagai assignee task \"{$task->title}\"",
                array_merge($this->activityLog->taskContext($task), [
                    'assignee_id' => $assigneeId,
                    'assignee_name' => $assignee?->name,
                ])
            );

            if ($assignee && (int) $assignee->id !== (int) $actor->id) {
                $assignee->notify(new \App\Notifications\TaskAssignedNotification($task, $actor));
            }

            return $task;
        });
    }

    public function update(Task $task, array $data, User $actor, string $editMode = 'full'): Task
    {
        return DB::transaction(function () use ($task, $data, $editMode, $actor) {
            if ($editMode === 'contributor') {
                $task->update(Arr::only($data, ['status', 'description']));
            } else {
                $task->update($this->preparePayload($data));
            }

            $task = $task->fresh(['assignee:id,name,avatar', 'creator:id,name,avatar', 'project:id,code,name']);

            $this->activityLog->log(
                $actor,
                'updated',
                $task,
                "Memperbarui task \"{$task->title}\"",
                $this->activityLog->taskContext($task)
            );

            return $task;
        });
    }

    public function delete(Task $task, User $actor): void
    {
        DB::transaction(function () use ($task, $actor) {
            $title = $task->title;
            $context = $this->activityLog->taskContext($task);

            $task->delete();

            $this->activityLog->log(
                $actor,
                'deleted',
                null,
                "Menghapus task \"{$title}\"",
                array_merge($context, ['task_title' => $title])
            );
        });
    }

    /** @param array<string, mixed> $data */
    private function preparePayload(array $data): array
    {
        return Arr::only($data, [
            'title',
            'description',
            'status',
            'priority',
            'assignee_id',
            'due_date',
            'start_date',
            'order',
            'estimated_hours',
            'actual_hours',
        ]);
    }
}
