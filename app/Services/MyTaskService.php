<?php

namespace App\Services;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Http\Resources\MyTaskListResource;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class MyTaskService
{
    private const PER_PAGE = 15;

    public function __construct(
        private readonly ProjectAccessService $accessService,
        private readonly ActivityLogService $activityLog
    ) {}

    /** @return array<string, mixed> */
    public function getIndexData(array $filters, User $user): array
    {
        $filters = $this->normalizeFilters($filters);

        $baseQuery = Task::query()->assignedTo($user);

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->whereNotIn('status', [TaskStatus::Done])->count(),
            'overdue' => (clone $baseQuery)
                ->whereNotIn('status', [TaskStatus::Done])
                ->whereDate('due_date', '<', now())
                ->count(),
            'done' => (clone $baseQuery)->where('status', TaskStatus::Done)->count(),
        ];

        $tasksQuery = (clone $baseQuery)
            ->with([
                'project:id,name,code,color,manager_id',
                'assignee:id,name',
            ])
            ->filter($filters)
            ->when(
                $filters['tab'] === 'done',
                fn ($q) => $q->where('status', TaskStatus::Done)->orderByDesc('updated_at'),
                fn ($q) => $q
                    ->whereNotIn('status', [TaskStatus::Done])
                    ->orderByRaw('due_date IS NULL')
                    ->orderBy('due_date')
                    ->orderByDesc('updated_at')
            );

        $tasks = $tasksQuery
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return [
            'tasks' => MyTaskListResource::collection($tasks),
            'filters' => $filters,
            'stats' => $stats,
            'tabs' => [
                ['id' => 'active', 'label' => 'Aktif', 'count' => $stats['pending']],
                ['id' => 'done', 'label' => 'Selesai', 'count' => $stats['done']],
            ],
            'filterOptions' => [
                'statuses' => TaskStatus::options(),
                'priorities' => TaskPriority::options(),
                'projects' => $this->getProjectFilterOptions($user),
            ],
        ];
    }

    /**
     * @param  list<int>  $taskIds
     * @return array{updated: int, skipped: int}
     */
    public function bulkUpdateStatus(array $taskIds, string $statusValue, User $user): array
    {
        $status = TaskStatus::from($statusValue);

        $tasks = Task::query()
            ->assignedTo($user)
            ->with('project:id,manager_id')
            ->whereIn('id', $taskIds)
            ->get();

        if ($tasks->isEmpty()) {
            throw new InvalidArgumentException('Tidak ada task valid yang dipilih.');
        }

        $updated = 0;
        $skipped = 0;

        DB::transaction(function () use ($tasks, $user, $status, &$updated, &$skipped) {
            foreach ($tasks as $task) {
                if ($task->status === $status) {
                    $skipped++;

                    continue;
                }

                if (! $this->accessService->canSetTaskStatusTo($user, $task->project, $task, $status)) {
                    $skipped++;

                    continue;
                }

                $task->update(['status' => $status]);
                $updated++;
            }

            if ($updated > 0) {
                $this->activityLog->log(
                    $user,
                    'bulk_status',
                    null,
                    "Mengubah status {$updated} task menjadi {$status->label()} (My Tasks)",
                    ['count' => $updated, 'status' => $status->value]
                );
            }
        });

        return ['updated' => $updated, 'skipped' => $skipped];
    }

    /** @param  array<string, mixed>  $filters */
    private function normalizeFilters(array $filters): array
    {
        $tab = in_array($filters['tab'] ?? '', ['active', 'done'], true)
            ? $filters['tab']
            : 'active';

        $status = $filters['status'] ?? '';
        if ($tab === 'done') {
            $status = '';
        }

        return [
            'tab' => $tab,
            'search' => trim((string) ($filters['search'] ?? '')),
            'status' => $status,
            'priority' => $filters['priority'] ?? '',
            'project_id' => $filters['project_id'] ?? '',
        ];
    }

    /** @return list<array{id: int, name: string, code: string}> */
    private function getProjectFilterOptions(User $user): array
    {
        return Project::query()
            ->accessibleBy($user)
            ->whereHas('tasks', fn ($q) => $q->assignedTo($user))
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->map(fn (Project $project) => [
                'id' => $project->id,
                'name' => $project->name,
                'code' => $project->code,
            ])
            ->values()
            ->all();
    }
}
