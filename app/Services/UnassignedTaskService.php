<?php

namespace App\Services;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Http\Resources\UnassignedTaskListResource;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class UnassignedTaskService
{
    private const PER_PAGE = 15;

    public function __construct(
        private readonly ProjectAccessService $accessService,
        private readonly TaskService $taskService,
        private readonly ActivityLogService $activityLog
    ) {}

    /** @return array<string, mixed> */
    public function getIndexData(array $filters, User $user): array
    {
        $filters = $this->normalizeFilters($filters);

        $baseQuery = Task::query()->unassignedInMemberProjects($user);

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
                'creator:id,name',
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

        $assigneesByProject = $this->buildAssigneesByProject(
            $user,
            $tasks->getCollection()->pluck('project')->unique('id')->filter()
        );

        return [
            'tasks' => UnassignedTaskListResource::collection($tasks),
            'filters' => $filters,
            'stats' => $stats,
            'tabs' => [
                ['id' => 'active', 'label' => 'Belum ditugaskan', 'count' => $stats['pending']],
                ['id' => 'done', 'label' => 'Selesai (tanpa assignee)', 'count' => $stats['done']],
            ],
            'filterOptions' => [
                'statuses' => TaskStatus::options(),
                'priorities' => TaskPriority::options(),
                'projects' => $this->getProjectFilterOptions($user),
            ],
            'assigneesByProject' => $assigneesByProject,
        ];
    }

    /**
     * @param  list<int>  $taskIds
     * @return array{updated: int, skipped: int}
     */
    public function bulkAssign(array $taskIds, int $assigneeId, User $user): array
    {
        $tasks = Task::query()
            ->unassignedInMemberProjects($user)
            ->whereNull('assignee_id')
            ->whereNotIn('status', [TaskStatus::Done])
            ->with('project')
            ->whereIn('id', $taskIds)
            ->get();

        if ($tasks->isEmpty()) {
            throw new InvalidArgumentException('Tidak ada task valid yang dipilih.');
        }

        $updated = 0;
        $skipped = 0;

        DB::transaction(function () use ($tasks, $user, $assigneeId, &$updated, &$skipped) {
            foreach ($tasks as $task) {
                $project = $task->project;

                if (! $project
                    || ! $this->accessService->canAssignTask($user, $project)
                    || ! $this->accessService->isAssignable($project, $assigneeId)
                ) {
                    $skipped++;

                    continue;
                }

                $this->taskService->assignAssignee($task, $assigneeId, $user);
                $updated++;
            }

            if ($updated > 0) {
                $assignee = User::query()->find($assigneeId);
                $this->activityLog->log(
                    $user,
                    'bulk_assigned',
                    null,
                    "Menetapkan {$assignee?->name} ke {$updated} task (Unassigned)",
                    [
                        'count' => $updated,
                        'assignee_id' => $assigneeId,
                        'assignee_name' => $assignee?->name,
                    ]
                );
            }
        });

        return ['updated' => $updated, 'skipped' => $skipped];
    }

    /**
     * @param  Collection<int, Project>  $projects
     * @return array<int, list<array{id: int, name: string, initials: string}>>
     */
    private function buildAssigneesByProject(User $user, Collection $projects): array
    {
        $map = [];

        foreach ($projects as $project) {
            if (! $this->accessService->canAssignTask($user, $project)) {
                continue;
            }

            $ids = $this->accessService->assignableUserIds($project);

            if ($ids === []) {
                $map[$project->id] = [];

                continue;
            }

            $map[$project->id] = User::query()
                ->where('is_active', true)
                ->whereIn('id', $ids)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map->toBrief()
                ->values()
                ->all();
        }

        return $map;
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
            ->whereUserIsMemberOrManager($user)
            ->whereHas('tasks', fn ($q) => $q->whereNull('assignee_id'))
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
