<?php

namespace App\Http\Controllers;

use App\Services\ReportExportService;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService,
        private readonly ReportExportService $reportExportService
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Reports/Index', $this->reportService->getIndexData(
            $request->user(),
            $request->integer('project') ?: null,
        ));
    }

    public function exportProjects(Request $request): StreamedResponse
    {
        return $this->reportExportService->exportProjectsCsv(
            $request->user(),
            $request->integer('project') ?: null,
        );
    }

    public function exportTasks(Request $request): StreamedResponse
    {
        return $this->reportExportService->exportTasksCsv(
            $request->user(),
            $request->integer('project') ?: null,
        );
    }
}
