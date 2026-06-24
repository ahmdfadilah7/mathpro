<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Reports/Index', $this->reportService->getIndexData(
            $request->user(),
            $request->integer('project') ?: null,
        ));
    }
}
