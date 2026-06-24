<?php

namespace App\Http\Controllers;

use App\Http\Requests\Task\BulkUpdateMyTaskStatusRequest;
use App\Services\MyTaskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MyTaskController extends Controller
{
    public function __construct(
        private readonly MyTaskService $myTaskService
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('MyTasks/Index', $this->myTaskService->getIndexData(
            $request->only(['search', 'status', 'priority', 'project_id', 'tab']),
            $request->user()
        ));
    }

    public function bulkUpdateStatus(BulkUpdateMyTaskStatusRequest $request): RedirectResponse
    {
        $result = $this->myTaskService->bulkUpdateStatus(
            $request->validated('task_ids'),
            $request->validated('status'),
            $request->user()
        );

        if ($result['updated'] === 0) {
            return back()->with('error', 'Tidak ada task yang diperbarui. Periksa izin status (Done hanya untuk PM).');
        }

        $message = "{$result['updated']} task berhasil diperbarui.";
        if ($result['skipped'] > 0) {
            $message .= " ({$result['skipped']} dilewati)";
        }

        return back()->with('success', $message);
    }
}
