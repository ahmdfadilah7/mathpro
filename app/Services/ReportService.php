<?php

namespace App\Services;

use App\Enums\ProjectStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ReportService
{
    /** @return array<string, mixed> */
    public function getIndexData(User $user, ?int $projectId = null): array
    {
        $projectId = $this->resolveProjectFilter($user, $projectId);

        $taskQuery = $this->accessibleTasksQuery($user, $projectId);
        $projectQuery = $this->accessibleProjectsQuery($user, $projectId);

        $totalTasks = (clone $taskQuery)->count();
        $doneTasks = (clone $taskQuery)->where('status', TaskStatus::Done)->count();

        return [
            'filters' => [
                'project_id' => $projectId,
            ],
            'filterOptions' => [
                'projects' => Project::query()
                    ->accessibleBy($user)
                    ->orderBy('name')
                    ->get(['id', 'code', 'name', 'color'])
                    ->map(fn (Project $p) => [
                        'id' => $p->id,
                        'code' => $p->code,
                        'name' => $p->name,
                        'color' => $p->color,
                    ])
                    ->values()
                    ->all(),
            ],
            'summary' => [
                'projects' => (clone $projectQuery)->count(),
                'tasks_total' => $totalTasks,
                'tasks_done' => $doneTasks,
                'completion_rate' => $totalTasks > 0
                    ? (int) round(($doneTasks / $totalTasks) * 100)
                    : 0,
                'tasks_open' => (clone $taskQuery)
                    ->whereNotIn('status', [TaskStatus::Done])
                    ->count(),
                'tasks_overdue' => (clone $taskQuery)
                    ->whereNotIn('status', [TaskStatus::Done])
                    ->whereDate('due_date', '<', now())
                    ->count(),
                'tasks_unassigned' => (clone $taskQuery)
                    ->whereNull('assignee_id')
                    ->whereNotIn('status', [TaskStatus::Done])
                    ->count(),
            ],
            'tasksByStatus' => $this->tasksGroupedByStatus($taskQuery),
            'tasksByPriority' => $this->tasksGroupedByPriority($taskQuery),
            'projectsByStatus' => $projectId
                ? []
                : $this->projectsGroupedByStatus($user),
            'projectReports' => $this->projectReports($user, $projectId),
            'teamWorkload' => $this->teamWorkload($user, $projectId),
        ];
    }

    private function resolveProjectFilter(User $user, ?int $projectId): ?int
    {
        if (! $projectId) {
            return null;
        }

        $exists = Project::query()
            ->accessibleBy($user)
            ->whereKey($projectId)
            ->exists();

        return $exists ? $projectId : null;
    }

    /** @return Builder<Task> */
    private function accessibleTasksQuery(User $user, ?int $projectId): Builder
    {
        return Task::query()
            ->whereHas('project', function (Builder $q) use ($user, $projectId) {
                $q->accessibleBy($user);
                if ($projectId) {
                    $q->whereKey($projectId);
                }
            });
    }

    /** @return Builder<Project> */
    private function accessibleProjectsQuery(User $user, ?int $projectId): Builder
    {
        return Project::query()
            ->accessibleBy($user)
            ->when($projectId, fn (Builder $q) => $q->whereKey($projectId));
    }

    /** @return list<array<string, mixed>> */
    private function tasksGroupedByStatus(Builder $taskQuery): array
    {
        $counts = (clone $taskQuery)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return collect(TaskStatus::cases())
            ->map(fn (TaskStatus $status) => [
                'value' => $status->value,
                'label' => $status->label(),
                'color' => $status->color(),
                'count' => (int) ($counts[$status->value] ?? 0),
            ])
            ->values()
            ->all();
    }

    /** @return list<array<string, mixed>> */
    private function tasksGroupedByPriority(Builder $taskQuery): array
    {
        $counts = (clone $taskQuery)
            ->selectRaw('priority, COUNT(*) as count')
            ->groupBy('priority')
            ->pluck('count', 'priority');

        return collect(TaskPriority::cases())
            ->map(fn (TaskPriority $priority) => [
                'value' => $priority->value,
                'label' => $priority->label(),
                'color' => $priority->color(),
                'count' => (int) ($counts[$priority->value] ?? 0),
            ])
            ->values()
            ->all();
    }

    /** @return list<array<string, mixed>> */
    private function projectsGroupedByStatus(User $user): array
    {
        $counts = Project::query()
            ->accessibleBy($user)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return collect(ProjectStatus::cases())
            ->map(fn (ProjectStatus $status) => [
                'value' => $status->value,
                'label' => $status->label(),
                'color' => $status->color(),
                'count' => (int) ($counts[$status->value] ?? 0),
            ])
            ->values()
            ->all();
    }

    /** @return list<array<string, mixed>> */
    private function projectReports(User $user, ?int $projectId): array
    {
        return Project::query()
            ->accessibleBy($user)
            ->when($projectId, fn (Builder $q) => $q->whereKey($projectId))
            ->with(['manager:id,name', 'department:id,name'])
            ->withCount([
                'tasks as tasks_total',
                'tasks as tasks_done' => fn (Builder $q) => $q->where('status', TaskStatus::Done),
                'tasks as tasks_open' => fn (Builder $q) => $q->whereNotIn('status', [TaskStatus::Done]),
                'tasks as tasks_overdue' => fn (Builder $q) => $q
                    ->whereNotIn('status', [TaskStatus::Done])
                    ->whereDate('due_date', '<', now()),
            ])
            ->orderByDesc('progress')
            ->orderBy('name')
            ->get()
            ->map(function (Project $project) {
                $total = (int) $project->tasks_total;
                $done = (int) $project->tasks_done;

                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'code' => $project->code,
                    'color' => $project->color,
                    'status' => $project->status->value,
                    'status_label' => $project->status->label(),
                    'status_color' => $project->status->color(),
                    'progress' => $project->progress,
                    'due_date' => $project->due_date?->format('d M Y'),
                    'department' => $project->department?->name,
                    'manager' => $project->manager?->toBrief(),
                    'tasks_total' => $total,
                    'tasks_done' => $done,
                    'tasks_open' => (int) $project->tasks_open,
                    'tasks_overdue' => (int) $project->tasks_overdue,
                    'completion_rate' => $total > 0 ? (int) round(($done / $total) * 100) : 0,
                ];
            })
            ->values()
            ->all();
    }

    /** @return list<array<string, mixed>> */
    private function teamWorkload(User $user, ?int $projectId): array
    {
        $tasks = Task::query()
            ->with(['assignee:id,name'])
            ->whereHas('project', function (Builder $q) use ($user, $projectId) {
                $q->accessibleBy($user);
                if ($projectId) {
                    $q->whereKey($projectId);
                }
            })
            ->whereNotNull('assignee_id')
            ->get();

        return $tasks
            ->groupBy('assignee_id')
            ->map(function ($group, $assigneeId) {
                $assignee = $group->first()->assignee;
                $open = $group->whereNotIn('status', [TaskStatus::Done]);
                $done = $group->where('status', TaskStatus::Done);

                $brief = $assignee?->toBrief();

                return [
                    'user_id' => (int) $assigneeId,
                    'name' => $brief['name'] ?? 'Unknown',
                    'initials' => $brief['initials'] ?? '?',
                    'tasks_total' => $group->count(),
                    'tasks_open' => $open->count(),
                    'tasks_done' => $done->count(),
                    'tasks_overdue' => $open->filter(
                        fn (Task $t) => $t->due_date?->isPast()
                    )->count(),
                ];
            })
            ->sortByDesc('tasks_open')
            ->values()
            ->all();
    }

    /** @return list<array<string, mixed>> */
    public function exportableTasks(User $user, ?int $projectId = null): array
    {
        $projectId = $this->resolveProjectFilter($user, $projectId);

        return $this->accessibleTasksQuery($user, $projectId)
            ->with(['project:id,code,name', 'assignee:id,name'])
            ->orderBy('project_id')
            ->orderBy('task_number')
            ->get()
            ->map(fn (Task $task) => [
                'task_number' => $task->task_number,
                'title' => $task->title,
                'project_code' => $task->project?->code,
                'status_label' => $task->status->label(),
                'priority_label' => $task->priority->label(),
                'assignee_name' => $task->assignee?->name,
                'due_date' => $task->due_date?->format('d M Y'),
            ])
            ->values()
            ->all();
    }
}
