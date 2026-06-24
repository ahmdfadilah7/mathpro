<?php

namespace App\Services;

use App\Http\Resources\TaskCommentResource;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class TaskCommentService
{
    public function __construct(
        private readonly ProjectAccessService $accessService,
        private readonly AttachmentService $attachmentService,
        private readonly ActivityLogService $activityLog
    ) {}

    /** @return list<array<string, mixed>> */
    public function listForTask(User $user, Project $project, Task $task): array
    {
        abort_unless($task->project_id === $project->id, 404);
        $this->accessService->authorizeView($user, $project);

        return TaskCommentResource::collection(
            $task->comments()
                ->with(['user:id,name,avatar', 'attachments'])
                ->orderBy('created_at')
                ->get()
        )->resolve();
    }

    /**
     * @param  list<UploadedFile>  $files
     * @return array<string, mixed>
     */
    public function store(User $user, Project $project, Task $task, ?string $body, array $files = []): array
    {
        abort_unless($task->project_id === $project->id, 404);
        $this->accessService->authorizeView($user, $project);

        $body = trim((string) $body);

        if ($body === '' && $files === []) {
            abort(422, 'Komentar atau lampiran wajib diisi.');
        }

        $comment = DB::transaction(function () use ($task, $user, $body, $files) {
            $comment = $task->comments()->create([
                'user_id' => $user->id,
                'body' => $body !== '' ? $body : null,
            ]);

            if ($files !== []) {
                $this->attachmentService->storeMany(
                    $comment,
                    $user,
                    $files,
                    'task-comments/'.$task->id
                );
            }

            return $comment->load(['user:id,name,avatar', 'attachments']);
        });

        $preview = $body !== '' ? mb_strimwidth($body, 0, 80, '…') : 'Lampiran';
        $this->activityLog->log(
            $user,
            'comment_sent',
            $comment,
            "Komentar pada task \"{$task->title}\": {$preview}",
            array_merge($this->activityLog->taskContext($task), ['task_id' => $task->id])
        );

        return (new TaskCommentResource($comment))->resolve();
    }

    public function canDeleteComment(User $user, TaskComment $comment): bool
    {
        $comment->loadMissing('task.project');
        $project = $comment->task?->project;

        if (! $project) {
            return false;
        }

        if ((int) $comment->user_id === (int) $user->id) {
            return true;
        }

        return $this->accessService->isFullTaskManager($user, $project);
    }

    public function delete(User $user, Project $project, Task $task, TaskComment $comment): void
    {
        abort_unless($task->project_id === $project->id, 404);
        abort_unless($comment->task_id === $task->id, 404);
        abort_unless($this->canDeleteComment($user, $comment), 403);

        DB::transaction(function () use ($comment, $user, $project, $task) {
            $this->attachmentService->deleteAllFor($comment);
            $comment->delete();

            $this->activityLog->log(
                $user,
                'comment_deleted',
                null,
                "Menghapus komentar pada task \"{$task->title}\"",
                $this->activityLog->taskContext($task)
            );
        });
    }
}
