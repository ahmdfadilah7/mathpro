<?php

namespace App\Http\Controllers;

use App\Enums\ProjectStatus;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly ActivityLogService $activityLogService
    ) {}

    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $accessibleProjects = Project::query()->accessibleBy($user);
        $accessibleProjectIds = (clone $accessibleProjects)->pluck('id');

        $myTasksQuery = Task::query()
            ->with(['project:id,name,code,color'])
            ->where('assignee_id', $user->id);

        $scopedTasksQuery = Task::query()->whereIn('project_id', $accessibleProjectIds);

        $stats = [
            'total_projects' => (clone $accessibleProjects)->count(),
            'active_projects' => (clone $accessibleProjects)
                ->where('status', ProjectStatus::Active)
                ->count(),
            'total_tasks' => (clone $scopedTasksQuery)->count(),
            'my_tasks' => (clone $myTasksQuery)->count(),
            'my_pending_tasks' => (clone $myTasksQuery)
                ->whereNotIn('status', [TaskStatus::Done])
                ->count(),
            'unassigned_tasks' => Task::query()->unassignedInMemberProjects($user)->count(),
            'overdue_tasks' => (clone $scopedTasksQuery)
                ->whereNotIn('status', [TaskStatus::Done])
                ->whereDate('due_date', '<', now())
                ->count(),
            'team_members' => $this->scopedTeamMemberCount($accessibleProjectIds),
        ];

        $projectsByStatus = (clone $accessibleProjects)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->map(fn ($count, $status) => [
                'status' => $status,
                'label' => ProjectStatus::tryFrom($status)?->label() ?? ucfirst($status),
                'count' => $count,
                'color' => ProjectStatus::tryFrom($status)?->color() ?? 'slate',
            ])
            ->values();

        $recentProjects = (clone $accessibleProjects)
            ->with(['department:id,name', 'manager:id,name,avatar'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (Project $project) => [
                'id' => $project->id,
                'name' => $project->name,
                'code' => $project->code,
                'status' => $project->status->value,
                'status_label' => $project->status->label(),
                'status_color' => $project->status->color(),
                'progress' => $project->progress,
                'due_date' => $project->due_date?->format('d M Y'),
                'department' => $project->department?->name,
                'manager' => $project->manager?->toBrief(),
                'color' => $project->color,
            ]);

        $myUpcomingTasks = Task::query()
            ->with(['project:id,name,code,color'])
            ->where('assignee_id', $user->id)
            ->whereNotIn('status', [TaskStatus::Done])
            ->orderBy('due_date')
            ->limit(6)
            ->get()
            ->map(fn (Task $task) => [
                'id' => $task->id,
                'title' => $task->title,
                'status' => $task->status->value,
                'status_label' => $task->status->label(),
                'priority' => $task->priority->value,
                'priority_label' => $task->priority->label(),
                'due_date' => $task->due_date?->format('d M Y'),
                'is_overdue' => $task->due_date?->isPast() ?? false,
                'project' => $task->project?->only(['name', 'code', 'color']),
            ]);

        $activityFeed = $this->activityLogService->recentFeed($user, 8);

        return Inertia::render('Dashboard/Index', [
            'stats' => $stats,
            'projectsByStatus' => $projectsByStatus,
            'recentProjects' => $recentProjects,
            'myUpcomingTasks' => $myUpcomingTasks,
            'activityFeed' => $activityFeed,
        ]);
    }

    /** @param  \Illuminate\Support\Collection<int, int>  $accessibleProjectIds */
    private function scopedTeamMemberCount($accessibleProjectIds): int
    {
        if ($accessibleProjectIds->isEmpty()) {
            return 0;
        }

        $memberIds = ProjectMember::query()
            ->whereIn('project_id', $accessibleProjectIds)
            ->pluck('user_id');

        $managerIds = Project::query()
            ->whereIn('id', $accessibleProjectIds)
            ->whereNotNull('manager_id')
            ->pluck('manager_id');

        return User::query()
            ->where('is_active', true)
            ->whereIn('id', $memberIds->merge($managerIds)->unique()->filter())
            ->count();
    }
}
