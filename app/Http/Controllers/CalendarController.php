<?php

namespace App\Http\Controllers;

use App\Services\CalendarService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CalendarController extends Controller
{
    public function __construct(
        private readonly CalendarService $calendarService
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Calendar/Index', $this->calendarService->getIndexData(
            $request->only(['tab', 'year', 'month', 'week', 'timeline_view', 'project_id']),
            $request->user()
        ));
    }
}
