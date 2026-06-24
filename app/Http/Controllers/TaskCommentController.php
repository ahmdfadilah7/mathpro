<?php

namespace App\Http\Controllers;

use App\Http\Requests\Task\StoreTaskCommentRequest;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskComment;
use App\Services\TaskCommentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TaskCommentController extends Controller
{
    public function __construct(
        private readonly TaskCommentService $taskCommentService
    ) {}

    public function index(Request $request, Project $project, Task $task): JsonResponse
    {
        return response()->json([
            'comments' => $this->taskCommentService->listForTask($request->user(), $project, $task),
        ]);
    }

    public function store(
        StoreTaskCommentRequest $request,
        Project $project,
        Task $task
    ): JsonResponse {
        $comment = $this->taskCommentService->store(
            $request->user(),
            $project,
            $task,
            $request->input('body'),
            $request->file('attachments', []) ?? []
        );

        return response()->json([
            'comment' => $comment,
        ], 201);
    }

    public function destroy(
        Request $request,
        Project $project,
        Task $task,
        TaskComment $comment
    ): RedirectResponse {
        $this->taskCommentService->delete($request->user(), $project, $task, $comment);

        return redirect()->route('chat.index', [
            'mode' => 'task',
            'task_project' => $project->id,
            'task' => $task->id,
        ]);
    }
}
