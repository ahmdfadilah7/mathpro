<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;

class GlobalSearchService
{
    private const MIN_LENGTH = 2;

    private const PROJECT_LIMIT = 5;

    private const TASK_LIMIT = 8;

    private const USER_LIMIT = 5;

    /** @return array<string, mixed> */
    public function search(User $user, ?string $query): array
    {
        $term = trim((string) $query);

        if (mb_strlen($term) < self::MIN_LENGTH) {
            return [
                'query' => $term,
                'projects' => [],
                'tasks' => [],
                'users' => [],
            ];
        }

        $like = '%'.$term.'%';

        $projects = Project::query()
            ->accessibleBy($user)
            ->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                    ->orWhere('code', 'like', $like);
            })
            ->orderBy('name')
            ->limit(self::PROJECT_LIMIT)
            ->get(['id', 'name', 'code', 'color', 'status'])
            ->map(fn (Project $project) => [
                'id' => $project->id,
                'type' => 'project',
                'title' => $project->name,
                'subtitle' => $project->code,
                'meta' => $project->status->label(),
                'color' => $project->color,
                'url' => route('projects.show', $project),
            ])
            ->values()
            ->all();

        $tasks = Task::query()
            ->with('project:id,name,code,color')
            ->where(function ($q) use ($user) {
                $q->where('assignee_id', $user->id)
                    ->orWhereHas('project', fn ($p) => $p->accessibleBy($user));
            })
            ->where(function ($q) use ($like, $term) {
                $q->where('title', 'like', $like)
                    ->orWhere('task_number', 'like', '%'.strtoupper($term).'%')
                    ->orWhere('description', 'like', $like);
            })
            ->orderByDesc('updated_at')
            ->limit(self::TASK_LIMIT)
            ->get()
            ->map(fn (Task $task) => [
                'id' => $task->id,
                'type' => 'task',
                'title' => $task->title,
                'subtitle' => ($task->task_number ?? '').($task->project ? ' · '.$task->project->code : ''),
                'meta' => $task->status->label(),
                'color' => $task->project?->color,
                'url' => $task->project
                    ? route('projects.show', $task->project).'#tasks'
                    : route('my-tasks.index'),
            ])
            ->values()
            ->all();

        $users = [];
        if ($user->role?->slug === 'super-admin') {
            $users = User::query()
                ->where('is_active', true)
                ->where(function ($q) use ($like) {
                    $q->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like);
                })
                ->orderBy('name')
                ->limit(self::USER_LIMIT)
                ->get(['id', 'name', 'email'])
                ->map(fn (User $u) => [
                    'id' => $u->id,
                    'type' => 'user',
                    'title' => $u->name,
                    'subtitle' => $u->email,
                    'meta' => 'User',
                    'color' => null,
                    'url' => route('users.edit', $u),
                ])
                ->values()
                ->all();
        }

        return [
            'query' => $term,
            'projects' => $projects,
            'tasks' => $tasks,
            'users' => $users,
        ];
    }
}
