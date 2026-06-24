<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityLogController extends Controller
{
    public function __construct(
        private readonly ActivityLogService $activityLogService
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Activity/Index', $this->activityLogService->getIndexData(
            $request->user(),
            $request->only(['search', 'action', 'user_id'])
        ));
    }
}
