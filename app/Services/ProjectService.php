<?php

namespace App\Services;

use App\Enums\ProjectMemberAccess;
use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Http\Resources\ProjectMemberResource;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\TaskResource;
use App\Models\Department;
use App\Models\Division;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ProjectService
{
    private const PER_PAGE = 10;

    public function __construct(
        private readonly ProjectAccessService $accessService,
        private readonly ActivityLogService $activityLog
    ) {}

    /** @return array<string, mixed> */
    public function getIndexData(array $filters, User $viewer): array
    {
        $filters = $this->normalizeFilters($filters);

        $projects = Project::query()
            ->accessibleBy($viewer)
            ->with(['division:id,name,code', 'department:id,name,code,division_id', 'manager:id,name'])
            ->withCount('tasks')
            ->filter($filters)
            ->latest()
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return [
            'projects' => ProjectResource::collection($projects),
            'filters' => $filters,
            'stats' => $this->getStats(),
            'filterOptions' => $this->getFilterOptions(),
        ];
    }

    /** @return array<string, mixed> */
    public function getFormOptions(): array
    {
        return [
            'divisions' => Division::query()
                ->where('is_active', true)
                ->with(['departments' => fn ($q) => $q->where('is_active', true)->select('id', 'division_id', 'name', 'code')])
                ->orderBy('name')
                ->get(['id', 'name', 'code'])
                ->map(fn (Division $division) => [
                    'id' => $division->id,
                    'name' => $division->name,
                    'code' => $division->code,
                    'departments' => $division->departments->map(fn (Department $dept) => [
                        'id' => $dept->id,
                        'name' => $dept->name,
                        'code' => $dept->code,
                    ])->values(),
                ]),
            'managers' => User::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map->toBrief(),
            'statuses' => ProjectStatus::options(),
            'priorities' => ProjectPriority::options(),
            'colorPresets' => ['#14b8a6', '#0ea5e9', '#8b5cf6', '#ec4899', '#f59e0b', '#ef4444', '#64748b'],
        ];
    }

    /** @return array<string, mixed> */
    public function getShowData(Project $project, User $viewer): array
    {
        $this->accessService->authorizeView($viewer, $project);

        $project->load([
            'division:id,name,code',
            'department:id,name,code,division_id',
            'manager:id,name',
            'members.user:id,name',
            'tasks' => fn ($q) => $q
                ->with(['assignee:id,name', 'creator:id,name', 'project:id,code'])
                ->orderBy('order')
                ->orderByDesc('id'),
        ]);
        $project->loadCount([
            'tasks',
            'tasks as tasks_done_count' => fn ($q) => $q->where('status', TaskStatus::Done),
        ]);

        $permissions = $this->accessService->permissionsFor($viewer, $project);
        $assignableUsers = $this->getAssignableUsers($project);

        return [
            'project' => (new ProjectResource($project))->resolve(),
            'permissions' => $permissions,
            'taskStats' => [
                'total' => $project->tasks_count,
                'done' => $project->tasks_done_count,
                'pending' => $project->tasks_count - $project->tasks_done_count,
            ],
            'tasks' => $project->tasks->map(function ($task) use ($viewer, $project) {
                return array_merge(
                    (new TaskResource($task))->resolve(),
                    $this->accessService->taskPermissionsFor($viewer, $project, $task)
                );
            })->values()->all(),
            'members' => ProjectMemberResource::collection($project->members)->resolve(),
            'memberAccessOptions' => ProjectMemberAccess::options(),
            'taskFormOptions' => [
                'next_task_number' => Task::generateTaskNumber($project->id),
                'statuses' => TaskStatus::options(),
                'priorities' => TaskPriority::options(),
                'assignees' => $assignableUsers,
            ],
            'availableUsers' => User::query()
                ->where('is_active', true)
                ->whereNot('id', $project->manager_id)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map->toBrief()
                ->values(),
        ];
    }

    /** @return list<array{id: int, name: string, initials: string}> */
    private function getAssignableUsers(Project $project): array
    {
        $currentAssigneeIds = $project->tasks
            ->pluck('assignee_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->all();

        $ids = $this->accessService->assignableUserIds($project, $currentAssigneeIds);

        if ($ids === []) {
            return [];
        }

        return User::query()
            ->where('is_active', true)
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map->toBrief()
            ->values()
            ->all();
    }

    public function create(array $data, User $actor): Project
    {
        return DB::transaction(function () use ($data, $actor) {
            $data['progress'] = $data['progress'] ?? 0;

            $project = Project::create($this->preparePayload($data));

            $this->activityLog->log(
                $actor,
                'created',
                $project,
                "Membuat project \"{$project->name}\" ({$project->code})",
                $this->activityLog->projectContext($project)
            );

            return $project;
        });
    }

    public function update(Project $project, array $data, User $actor): Project
    {
        return DB::transaction(function () use ($project, $data, $actor) {
            $project->update($this->preparePayload($data));
            $project = $project->fresh();

            $this->activityLog->log(
                $actor,
                'updated',
                $project,
                "Memperbarui project \"{$project->name}\" ({$project->code})",
                $this->activityLog->projectContext($project)
            );

            return $project;
        });
    }

    public function delete(Project $project, User $actor): void
    {
        DB::transaction(function () use ($project, $actor) {
            $name = $project->name;
            $code = $project->code;
            $context = $this->activityLog->projectContext($project);

            $project->delete();

            $this->activityLog->log(
                $actor,
                'deleted',
                null,
                "Menghapus project \"{$name}\" ({$code})",
                $context
            );
        });
    }

    /** @return array<string, int> */
    public function getStats(): array
    {
        return [
            'total' => Project::count(),
            'active' => Project::where('status', ProjectStatus::Active)->count(),
            'planning' => Project::where('status', ProjectStatus::Planning)->count(),
            'completed' => Project::where('status', ProjectStatus::Completed)->count(),
        ];
    }

    /** @return array<string, mixed> */
    private function getFilterOptions(): array
    {
        return [
            'divisions' => Division::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'departments' => Department::where('is_active', true)->orderBy('name')->get(['id', 'name', 'division_id']),
            'statuses' => ProjectStatus::options(),
            'priorities' => ProjectPriority::options(),
            'managers' => User::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ];
    }

    /** @param array<string, mixed> $filters */
    private function normalizeFilters(array $filters): array
    {
        $normalized = [
            'search' => $filters['search'] ?? null,
            'status' => $filters['status'] ?? null,
            'priority' => $filters['priority'] ?? null,
            'division_id' => $filters['division_id'] ?? null,
            'department_id' => $filters['department_id'] ?? null,
            'manager_id' => $filters['manager_id'] ?? null,
        ];

        return array_map(
            fn ($value) => filled($value) ? $value : null,
            $normalized
        );
    }

    /** @param array<string, mixed> $data */
    private function preparePayload(array $data): array
    {
        return Arr::only($data, [
            'name',
            'code',
            'description',
            'division_id',
            'department_id',
            'manager_id',
            'status',
            'priority',
            'progress',
            'start_date',
            'due_date',
            'budget',
            'color',
        ]);
    }
}
