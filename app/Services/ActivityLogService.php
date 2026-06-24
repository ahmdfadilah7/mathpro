<?php

namespace App\Services;

use App\Http\Resources\ActivityLogResource;
use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ActivityLogService
{
    /** @var array<string, array{label: string, color: string}> */
    public const ACTION_META = [
        'created' => ['label' => 'Dibuat', 'color' => 'emerald'],
        'updated' => ['label' => 'Diperbarui', 'color' => 'brand'],
        'deleted' => ['label' => 'Dihapus', 'color' => 'rose'],
        'status_changed' => ['label' => 'Status diubah', 'color' => 'amber'],
        'assigned' => ['label' => 'Assignee ditetapkan', 'color' => 'sky'],
        'unassigned' => ['label' => 'Assignee dicabut', 'color' => 'slate'],
        'members_synced' => ['label' => 'Tim diperbarui', 'color' => 'indigo'],
        'message_sent' => ['label' => 'Pesan chat', 'color' => 'brand'],
        'message_deleted' => ['label' => 'Pesan dihapus', 'color' => 'rose'],
        'comment_sent' => ['label' => 'Komentar task', 'color' => 'brand'],
        'comment_deleted' => ['label' => 'Komentar dihapus', 'color' => 'rose'],
        'login' => ['label' => 'Login', 'color' => 'slate'],
        'logout' => ['label' => 'Logout', 'color' => 'slate'],
        'bulk_assigned' => ['label' => 'Bulk assign', 'color' => 'sky'],
        'bulk_status' => ['label' => 'Bulk status', 'color' => 'amber'],
    ];

    /** @return array<string, mixed> */
    public function projectContext(Project $project): array
    {
        return [
            'project_id' => $project->id,
            'project_code' => $project->code,
            'project_name' => $project->name,
        ];
    }

    /** @return array<string, mixed> */
    public function taskContext(Task $task): array
    {
        $task->loadMissing('project:id,code,name');

        return $task->project
            ? $this->projectContext($task->project)
            : [];
    }

    public function log(
        ?User $actor,
        string $action,
        ?Model $subject,
        string $description,
        array $properties = [],
        ?Request $request = null
    ): ActivityLog {
        return ActivityLog::create([
            'user_id' => $actor?->id,
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'description' => $description,
            'properties' => $properties === [] ? null : $properties,
            'ip_address' => $request?->ip(),
        ]);
    }

    /** @return list<array<string, mixed>> */
    public function recentFeed(User $viewer, int $limit = 8): array
    {
        return ActivityLogResource::collection(
            $this->visibleTo($viewer)
                ->with('user:id,name,avatar')
                ->latest()
                ->limit($limit)
                ->get()
        )->resolve();
    }

    /** @return array<string, mixed> */
    public function getIndexData(User $viewer, array $filters): array
    {
        $filters = [
            'search' => trim((string) ($filters['search'] ?? '')),
            'action' => (string) ($filters['action'] ?? ''),
            'user_id' => filled($filters['user_id'] ?? null) ? (int) $filters['user_id'] : null,
        ];

        $logs = $this->visibleTo($viewer)
            ->with('user:id,name,avatar')
            ->when($filters['search'], function (Builder $q, string $search) {
                $q->where(function (Builder $inner) use ($search) {
                    $inner->where('description', 'like', "%{$search}%")
                        ->orWhereHas('user', fn (Builder $u) => $u->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($filters['action'], fn (Builder $q, string $action) => $q->where('action', $action))
            ->when($filters['user_id'], fn (Builder $q, int $id) => $q->where('user_id', $id))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return [
            'logs' => ActivityLogResource::collection($logs),
            'filters' => $filters,
            'actionOptions' => collect(self::ACTION_META)
                ->map(fn (array $meta, string $key) => [
                    'value' => $key,
                    'label' => $meta['label'],
                ])
                ->values()
                ->all(),
            'userOptions' => User::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name])
                ->values()
                ->all(),
        ];
    }

    /** @return Builder<ActivityLog> */
    public function visibleTo(User $viewer): Builder
    {
        if ($viewer->role?->slug === 'super-admin') {
            return ActivityLog::query();
        }

        $projectIds = Project::query()
            ->accessibleBy($viewer)
            ->pluck('id');

        return ActivityLog::query()->where(function (Builder $q) use ($viewer, $projectIds) {
            $q->where('user_id', $viewer->id);

            if ($projectIds->isNotEmpty()) {
                $q->orWhere(function (Builder $inner) use ($projectIds) {
                    $inner->where('subject_type', Project::class)
                        ->whereIn('subject_id', $projectIds);
                })->orWhere(function (Builder $inner) use ($projectIds) {
                    $inner->where('subject_type', Task::class)
                        ->whereIn('subject_id', Task::query()->whereIn('project_id', $projectIds)->select('id'));
                })->orWhere(function (Builder $inner) use ($projectIds) {
                    foreach ($projectIds as $projectId) {
                        $inner->orWhereJsonContains('properties->project_id', $projectId);
                    }
                });
            }
        });
    }

    public static function actionLabel(string $action): string
    {
        return self::ACTION_META[$action]['label'] ?? Str::headline(str_replace('_', ' ', $action));
    }

    public static function actionColor(string $action): string
    {
        return self::ACTION_META[$action]['color'] ?? 'slate';
    }
}
