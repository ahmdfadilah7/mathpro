<?php

namespace App\Services;

use App\Enums\TaskStatus;
use App\Models\Conversation;
use App\Models\NotificationDismissal;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Collection;

class NotificationService
{
    private const LIMIT = 15;

    /** @return list<array<string, mixed>> */
    public function forUser(User $user): array
    {
        $dismissed = $this->dismissedKeys($user);

        return collect($this->generateNotifications($user))
            ->reject(fn (array $item) => in_array($item['id'], $dismissed, true))
            ->sortByDesc('sort_at')
            ->take(self::LIMIT)
            ->map(fn (array $item) => collect($item)->except('sort_at')->all())
            ->values()
            ->all();
    }

    public function unreadCountFor(User $user): int
    {
        return count($this->forUser($user));
    }

    public function dismiss(User $user, string $notificationKey): void
    {
        NotificationDismissal::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'notification_key' => $notificationKey,
            ],
            ['dismissed_at' => now()]
        );
    }

    public function dismissAll(User $user): int
    {
        $keys = collect($this->generateNotifications($user))->pluck('id');

        foreach ($keys as $key) {
            $this->dismiss($user, (string) $key);
        }

        return $keys->count();
    }

    /** @return list<string> */
    private function dismissedKeys(User $user): array
    {
        return NotificationDismissal::query()
            ->where('user_id', $user->id)
            ->pluck('notification_key')
            ->all();
    }

    /** @return Collection<int, array<string, mixed>> */
    private function generateNotifications(User $user): Collection
    {
        return collect()
            ->merge($this->taskNotifications($user))
            ->merge($this->unassignedNotification($user))
            ->merge($this->chatNotifications($user));
    }

    /** @return Collection<int, array<string, mixed>> */
    private function taskNotifications(User $user): Collection
    {
        $base = Task::query()
            ->assignedTo($user)
            ->whereNot('status', TaskStatus::Done)
            ->with('project:id,name,code,color');

        $overdue = (clone $base)
            ->whereDate('due_date', '<', now())
            ->orderBy('due_date')
            ->limit(5)
            ->get();

        $dueToday = (clone $base)
            ->whereDate('due_date', now())
            ->orderBy('due_date')
            ->limit(5)
            ->get();

        $items = collect();

        foreach ($overdue as $task) {
            $items->push($this->taskItem(
                $task,
                'task_overdue',
                'Task terlambat',
                'Jatuh tempo '.$task->due_date->format('d M Y'),
                'rose'
            ));
        }

        $overdueIds = $overdue->pluck('id');

        foreach ($dueToday as $task) {
            if ($overdueIds->contains($task->id)) {
                continue;
            }

            $items->push($this->taskItem(
                $task,
                'task_due_today',
                'Jatuh tempo hari ini',
                $task->project?->name ?? 'Task',
                'amber'
            ));
        }

        return $items;
    }

    /** @return Collection<int, array<string, mixed>> */
    private function unassignedNotification(User $user): Collection
    {
        $count = Task::query()
            ->unassignedInMemberProjects($user)
            ->whereNot('status', TaskStatus::Done)
            ->count();

        if ($count === 0) {
            return collect();
        }

        return collect([[
            'id' => 'unassigned-summary',
            'type' => 'unassigned',
            'title' => 'Task belum ditugaskan',
            'body' => "{$count} task menunggu assignee di project Anda",
            'href' => route('unassigned.index'),
            'color' => 'sky',
            'sort_at' => now()->timestamp,
            'created_at_label' => 'Perlu tindakan',
        ]]);
    }

    /** @return Collection<int, array<string, mixed>> */
    private function chatNotifications(User $user): Collection
    {
        $projectIds = Project::query()
            ->whereUserIsMemberOrManager($user)
            ->pluck('id');

        if ($projectIds->isEmpty()) {
            return collect();
        }

        $conversations = Conversation::query()
            ->where('type', 'project')
            ->whereIn('project_id', $projectIds)
            ->with(['project:id,name,code,color', 'latestMessage.user:id,name'])
            ->get();

        $items = collect();

        foreach ($conversations as $conversation) {
            $latest = $conversation->latestMessage;
            $project = $conversation->project;

            if (! $latest || ! $project || (int) $latest->user_id === (int) $user->id) {
                continue;
            }

            $pivot = $conversation->participants()
                ->where('users.id', $user->id)
                ->first()?->pivot;

            $unread = $pivot
                && (! $pivot->last_read_at || $latest->created_at->gt($pivot->last_read_at));

            if (! $unread) {
                continue;
            }

            $preview = $latest->body
                ? mb_strimwidth(strip_tags($latest->body), 0, 80, '…')
                : 'Lampiran baru';

            $items->push([
                'id' => 'chat-'.$conversation->id,
                'type' => 'chat',
                'title' => "Chat {$project->code}",
                'body' => ($latest->user?->name ? $latest->user->name.': ' : '').$preview,
                'href' => route('chat.index', ['project' => $project->id]),
                'color' => 'brand',
                'sort_at' => $latest->created_at->timestamp,
                'created_at_label' => $latest->created_at->diffForHumans(),
            ]);
        }

        return $items->sortByDesc('sort_at')->values();
    }

    /** @return array<string, mixed> */
    private function taskItem(Task $task, string $type, string $title, string $body, string $color): array
    {
        return [
            'id' => $type.'-'.$task->id,
            'type' => $type,
            'title' => $title,
            'body' => $task->title.' — '.$body,
            'href' => $task->project
                ? route('projects.show', $task->project).'#tasks'
                : route('my-tasks.index'),
            'color' => $color,
            'sort_at' => $task->due_date?->timestamp ?? $task->updated_at->timestamp,
            'created_at_label' => $task->due_date?->diffForHumans() ?? $task->updated_at->diffForHumans(),
        ];
    }
}
