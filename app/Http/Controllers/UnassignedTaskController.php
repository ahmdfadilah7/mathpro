<?php

namespace App\Http\Controllers;

use App\Http\Requests\Task\AssignUnassignedTaskRequest;
use App\Http\Requests\Task\BulkAssignUnassignedTaskRequest;
use App\Models\Task;
use App\Services\ProjectAccessService;
use App\Services\TaskService;
use App\Services\UnassignedTaskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Inertia\Inertia;
use Inertia\Response;

class UnassignedTaskController extends Controller
{
    public function __construct(
        private readonly UnassignedTaskService $unassignedTaskService,
        private readonly TaskService $taskService,
        private readonly ProjectAccessService $accessService
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Unassigned/Index', $this->unassignedTaskService->getIndexData(
            $request->only(['tab', 'search', 'status', 'priority', 'project_id']),
            $request->user()
        ));
    }

    public function assign(AssignUnassignedTaskRequest $request, Task $task): RedirectResponse
    {
        $task->load('project');

        abort_unless($task->assignee_id === null, 422, 'Task sudah memiliki assignee.');
        $this->accessService->authorizeAssignTask($request->user(), $task->project);

        abort_unless(
            $task->project && $this->accessService->isMemberOrManager($request->user(), $task->project),
            403
        );

        $this->taskService->assignAssignee(
            $task,
            (int) $request->validated('assignee_id'),
            $request->user()
        );

        return back()->with('swal', [
            'title' => 'Berhasil!',
            'message' => 'Assignee task berhasil ditetapkan.',
        ]);
    }

    public function bulkAssign(BulkAssignUnassignedTaskRequest $request): RedirectResponse
    {
        try {
            $result = $this->unassignedTaskService->bulkAssign(
                $request->validated('task_ids'),
                (int) $request->validated('assignee_id'),
                $request->user()
            );
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        if ($result['updated'] === 0) {
            return back()->with(
                'error',
                'Tidak ada task yang ditugaskan. Periksa izin atau assignee yang valid untuk semua project terpilih.'
            );
        }

        $message = "{$result['updated']} task berhasil ditugaskan.";
        if ($result['skipped'] > 0) {
            $message .= " ({$result['skipped']} dilewati)";
        }

        return back()->with('success', $message);
    }
}
