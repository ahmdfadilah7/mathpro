<?php

namespace App\Http\Controllers;

use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Http\Requests\Task\UpdateTaskStatusRequest;
use App\Models\Project;
use App\Models\Task;
use App\Services\ProjectAccessService;
use App\Services\TaskService;
use Illuminate\Http\RedirectResponse;

class ProjectTaskController extends Controller
{
    public function __construct(
        private readonly TaskService $taskService,
        private readonly ProjectAccessService $accessService
    ) {}

    public function store(StoreTaskRequest $request, Project $project): RedirectResponse
    {
        $this->accessService->authorizeCreateTask($request->user(), $project);

        $task = $this->taskService->create($project, $request->validated(), $request->user());

        return redirect()
            ->back()
            ->with('swal', [
                'title' => 'Berhasil!',
                'message' => "Task \"{$task->title}\" berhasil ditambahkan.",
                'redirect' => route('projects.show', $project).'#tasks',
            ]);
    }

    public function update(UpdateTaskRequest $request, Project $project, Task $task): RedirectResponse
    {
        abort_unless($task->project_id === $project->id, 404);

        $this->accessService->authorizeUpdateTask($request->user(), $project, $task);

        $task = $this->taskService->update(
            $task,
            $request->validated(),
            $request->user(),
            $request->editMode()
        );

        return redirect()
            ->back()
            ->with('swal', [
                'title' => 'Berhasil!',
                'message' => 'Task berhasil diperbarui.',
                'redirect' => route('projects.show', $project).'#tasks',
            ]);
    }

    public function updateStatus(UpdateTaskStatusRequest $request, Project $project, Task $task): RedirectResponse
    {
        abort_unless($task->project_id === $project->id, 404);

        $this->accessService->authorizeUpdateTask($request->user(), $project, $task);

        $this->taskService->updateStatus($task, $request->validated('status'), $request->user());

        return back();
    }

    public function destroy(Project $project, Task $task): RedirectResponse
    {
        abort_unless($task->project_id === $project->id, 404);

        $user = request()->user();
        $this->accessService->authorizeDeleteTask($user, $project);

        $title = $task->title;
        $this->taskService->delete($task, $user);

        return redirect()
            ->back()
            ->with('swal', [
                'title' => 'Terhapus!',
                'message' => "Task \"{$title}\" berhasil dihapus.",
                'redirect' => route('projects.show', $project).'#tasks',
            ]);
    }
}
