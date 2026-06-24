<?php

namespace App\Services;

use App\Enums\TaskStatus;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ChatService
{
    public function __construct(
        private readonly ProjectAccessService $accessService,
        private readonly AttachmentService $attachmentService,
        private readonly TaskCommentService $taskCommentService,
        private readonly ActivityLogService $activityLog
    ) {}

    /** @return array<string, mixed> */
    public function getIndexData(
        User $user,
        string $mode = 'team',
        ?int $conversationId = null,
        ?int $projectId = null,
        ?int $taskProjectId = null,
        ?int $taskId = null
    ): array {
        $mode = in_array($mode, ['team', 'task'], true) ? $mode : 'team';

        $messages = [];
        $activePanel = null;

        if ($mode === 'task') {
            $taskProjects = $this->getTaskChatProjects($user);
            $taskThreads = $this->getTaskThreads($user);

            if ($taskProjectId && $taskId) {
                $task = Task::query()
                    ->with(['project:id,name,code,color'])
                    ->find($taskId);

                if ($task && $task->project_id === $taskProjectId) {
                    $this->accessService->authorizeView($user, $task->project);
                    $activePanel = $this->mapActiveTask($task);
                    $messages = $this->taskCommentService->listForTask($user, $task->project, $task);
                }
            }

            return [
                'mode' => 'task',
                'teamProjects' => [],
                'taskProjects' => $taskProjects,
                'taskThreads' => $taskThreads,
                'activePanel' => $activePanel,
                'messages' => $messages,
                'filters' => [
                    'mode' => 'task',
                    'conversation_id' => null,
                    'project_id' => null,
                    'task_project_id' => $taskProjectId,
                    'task_id' => $taskId,
                ],
            ];
        }

        $teamProjects = $this->getTeamProjects($user);

        if ($projectId) {
            $conversation = $this->findOrCreateProjectConversation($user, $projectId);
            $conversationId = $conversation->id;
        }

        if ($conversationId) {
            $conversation = Conversation::query()
                ->with($this->conversationProjectRelationsForSync())
                ->where('type', 'project')
                ->whereHas('participants', fn ($q) => $q->where('users.id', $user->id))
                ->find($conversationId);

            if ($conversation) {
                abort_unless(
                    $this->accessService->isMemberOrManager($user, $conversation->project),
                    403
                );
                $this->syncProjectParticipants($conversation, $conversation->project, $user);
                $this->markAsRead($conversation, $user);
                $activePanel = $this->mapActiveTeam($conversation, $user);
                $messages = MessageResource::collection(
                    $conversation->messages()
                        ->with([
                            'user:id,name,avatar',
                            'attachments',
                            'conversation.project:id,manager_id',
                        ])
                        ->orderBy('created_at')
                        ->get()
                )->resolve();
            }
        }

        return [
            'mode' => 'team',
            'teamProjects' => $teamProjects,
            'taskProjects' => [],
            'taskThreads' => [],
            'activePanel' => $activePanel,
            'messages' => $messages,
            'filters' => [
                'mode' => 'team',
                'conversation_id' => $conversationId,
                'project_id' => $projectId,
                'task_project_id' => null,
                'task_id' => null,
            ],
        ];
    }

    public function findOrCreateProjectConversation(User $user, int $projectId): Conversation
    {
        $project = Project::query()
            ->with(['manager:id', 'members:id,project_id,user_id'])
            ->findOrFail($projectId);

        abort_unless($this->accessService->isMemberOrManager($user, $project), 403);

        $conversation = Conversation::query()->firstOrCreate(
            [
                'project_id' => $project->id,
                'participant_key' => Conversation::PROJECT_KEY,
            ],
            ['type' => 'project']
        );

        $this->syncProjectParticipants($conversation, $project, $user);

        abort_unless(
            $conversation->participants()->where('users.id', $user->id)->exists(),
            403
        );

        return $conversation;
    }

    /** @return list<string> */
    private function conversationProjectRelationsForSync(): array
    {
        return [
            'project.manager:id',
            'project.members:id,project_id,user_id',
        ];
    }

    private function syncProjectParticipants(Conversation $conversation, Project $project, ?User $accessingUser = null): void
    {
        if (! $project->relationLoaded('members')) {
            $project->load(['manager:id', 'members:id,project_id,user_id']);
        }

        $userIds = collect([(int) $project->manager_id])
            ->merge($project->members->pluck('user_id'))
            ->filter()
            ->unique()
            ->values();

        if ($accessingUser && $this->accessService->isMemberOrManager($accessingUser, $project)) {
            $userIds->push((int) $accessingUser->id);
            $userIds = $userIds->unique()->values();
        }

        $attachedIds = $conversation->participants()->pluck('users.id');

        foreach ($userIds->diff($attachedIds) as $userId) {
            $conversation->participants()->attach($userId, ['last_read_at' => null]);
        }

        $toDetach = $attachedIds->diff($userIds);
        if ($toDetach->isNotEmpty()) {
            $conversation->participants()->detach($toDetach->all());
        }
    }

    /**
     * @param  list<UploadedFile>  $files
     */
    public function sendMessage(Conversation $conversation, User $user, ?string $body, array $files = []): Message
    {
        $conversation->loadMissing('project');

        abort_unless(
            $conversation->project
            && $this->accessService->isMemberOrManager($user, $conversation->project),
            403
        );

        if ($conversation->type === 'project') {
            $conversation->loadMissing($this->conversationProjectRelationsForSync());
            $this->syncProjectParticipants($conversation, $conversation->project, $user);
        }

        abort_unless(
            $conversation->participants()->where('users.id', $user->id)->exists(),
            403
        );

        $body = trim((string) $body);

        if ($body === '' && $files === []) {
            abort(422, 'Pesan atau lampiran wajib diisi.');
        }

        return DB::transaction(function () use ($conversation, $user, $body, $files) {
            $message = $conversation->messages()->create([
                'user_id' => $user->id,
                'body' => $body !== '' ? $body : null,
            ]);

            if ($files !== []) {
                $this->attachmentService->storeMany(
                    $message,
                    $user,
                    $files,
                    'chat/'.$conversation->id
                );
            }

            $conversation->participants()->updateExistingPivot($user->id, [
                'last_read_at' => now(),
            ]);

            $message = $message->load(['user:id,name,avatar', 'attachments']);

            if ($conversation->type === 'project' && $conversation->project) {
                $project = $conversation->project;
                $preview = $body !== '' ? mb_strimwidth($body, 0, 80, '…') : 'Lampiran';
                $this->activityLog->log(
                    $user,
                    'message_sent',
                    $message,
                    "Mengirim pesan di chat tim {$project->code}: {$preview}",
                    $this->activityLog->projectContext($project)
                );
            }

            return $message;
        });
    }

    public function canDeleteMessage(User $user, Message $message): bool
    {
        if ((int) $message->user_id === (int) $user->id) {
            return true;
        }

        $message->loadMissing('conversation.project');

        return $message->conversation?->project
            && $this->accessService->isFullTaskManager($user, $message->conversation->project);
    }

    public function deleteMessage(User $user, Conversation $conversation, Message $message): void
    {
        abort_unless((int) $message->conversation_id === (int) $conversation->id, 404);
        abort_unless($this->canDeleteMessage($user, $message), 403);

        $message->loadMissing('conversation.project');
        $project = $message->conversation?->project;

        DB::transaction(function () use ($message, $user, $project) {
            $this->attachmentService->deleteAllFor($message);
            $message->delete();

            if ($project) {
                $this->activityLog->log(
                    $user,
                    'message_deleted',
                    null,
                    "Menghapus pesan di chat tim {$project->code}",
                    $this->activityLog->projectContext($project)
                );
            }
        });
    }

    public function markAsRead(Conversation $conversation, User $user): void
    {
        $conversation->participants()->updateExistingPivot($user->id, [
            'last_read_at' => now(),
        ]);
    }

    /** @return array<string, mixed> */
    private function mapActiveTeam(Conversation $conversation, User $user): array
    {
        $project = $conversation->project;
        $participantCount = $conversation->participants()->count();

        return [
            'type' => 'team',
            'id' => $conversation->id,
            'project' => [
                'id' => $project->id,
                'code' => $project->code,
                'name' => $project->name,
                'color' => $project->color,
            ],
            'title' => $project->name,
            'subtitle' => "Percakapan project · {$project->code} · {$participantCount} anggota",
        ];
    }

    /** @return array<string, mixed> */
    private function mapActiveTask(Task $task): array
    {
        return [
            'type' => 'task',
            'id' => $task->id,
            'task_number' => $task->task_number,
            'title' => $task->title,
            'project' => [
                'id' => $task->project->id,
                'code' => $task->project->code,
                'name' => $task->project->name,
                'color' => $task->project->color,
            ],
            'subtitle' => 'Diskusi komentar task · semua anggota project',
        ];
    }

    /** @return list<array<string, mixed>> */
    private function getTaskThreads(User $user): array
    {
        return Task::query()
            ->whereHas('project', fn ($q) => $q->accessibleBy($user))
            ->whereHas('comments')
            ->with([
                'project:id,name,code,color',
                'latestComment.user:id,name,avatar',
            ])
            ->withCount('comments')
            ->get()
            ->sortByDesc(fn (Task $t) => ($t->latestComment?->created_at ?? $t->updated_at)?->getTimestamp() ?? 0)
            ->values()
            ->map(fn (Task $t) => $this->mapTaskThread($t))
            ->all();
    }

    /** @return list<array<string, mixed>> */
    private function getTaskChatProjects(User $user): array
    {
        return Project::query()
            ->accessibleBy($user)
            ->whereHas('tasks')
            ->with([
                'tasks' => fn ($q) => $q
                    ->whereNotIn('status', [TaskStatus::Done])
                    ->withCount('comments')
                    ->with(['latestComment.user:id,name,avatar'])
                    ->orderBy('order')
                    ->orderBy('id'),
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (Project $project) => [
                'id' => $project->id,
                'name' => $project->name,
                'code' => $project->code,
                'color' => $project->color,
                'tasks' => $project->tasks->map(fn (Task $t) => $this->mapTaskThread($t))->values()->all(),
            ])
            ->values()
            ->all();
    }

    /** @return array<string, mixed> */
    private function mapTaskThread(Task $task): array
    {
        $latest = $task->latestComment;
        $preview = null;

        if ($latest) {
            $preview = $latest->body
                ? mb_strimwidth(strip_tags($latest->body), 0, 80, '…')
                : '📎 Lampiran';
        }

        return [
            'id' => $task->id,
            'project_id' => $task->project_id,
            'task_number' => $task->task_number,
            'title' => $task->title,
            'project' => $task->relationLoaded('project') ? [
                'id' => $task->project->id,
                'code' => $task->project->code,
                'name' => $task->project->name,
                'color' => $task->project->color,
            ] : null,
            'comments_count' => $task->comments_count ?? $task->comments()->count(),
            'last_message' => $latest ? [
                'preview' => $preview,
                'created_at_label' => $latest->created_at?->diffForHumans(),
                'user_name' => $latest->user?->name,
            ] : null,
        ];
    }

    /** @return list<array<string, mixed>> */
    private function getTeamProjects(User $user): array
    {
        $projects = Project::query()
            ->whereUserIsMemberOrManager($user)
            ->withCount('members')
            ->orderBy('name')
            ->get();

        $conversations = Conversation::query()
            ->where('type', 'project')
            ->where('participant_key', Conversation::PROJECT_KEY)
            ->whereIn('project_id', $projects->pluck('id'))
            ->with(['latestMessage.user:id,name,avatar'])
            ->get()
            ->keyBy('project_id');

        return $projects
            ->map(function (Project $project) use ($user, $conversations) {
                $conversation = $conversations->get($project->id);
                $latest = $conversation?->latestMessage;
                $pivot = $conversation?->participants()->where('users.id', $user->id)->first()?->pivot;
                $unread = $latest
                    && (int) $latest->user_id !== (int) $user->id
                    && $pivot
                    && (! $pivot->last_read_at || $latest->created_at->gt($pivot->last_read_at));

                $memberCount = (int) $project->members_count + ($project->manager_id ? 1 : 0);

                return [
                    'id' => $project->id,
                    'conversation_id' => $conversation?->id,
                    'name' => $project->name,
                    'code' => $project->code,
                    'color' => $project->color,
                    'member_count' => $memberCount,
                    'last_message' => $latest ? [
                        'preview' => $latest->body
                            ? ($latest->user?->name ? $latest->user->name.': ' : '')
                                .mb_strimwidth(strip_tags($latest->body), 0, 60, '…')
                            : ($latest->user?->name ?? 'Seseorang').': 📎 Lampiran',
                        'created_at_label' => $latest->created_at?->diffForHumans(),
                    ] : null,
                    'unread' => (bool) $unread,
                ];
            })
            ->sortByDesc(fn (array $row) => $conversations->get($row['id'])?->latestMessage?->created_at?->getTimestamp() ?? 0)
            ->values()
            ->all();
    }

}
