<?php

namespace App\Services;

use App\Models\User;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportService
{
    public function __construct(
        private readonly ReportService $reportService
    ) {}

    public function exportProjectsCsv(User $user, ?int $projectId = null): StreamedResponse
    {
        $data = $this->reportService->getIndexData($user, $projectId);
        $filename = 'mathpro-laporan-project-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($data) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'Kode',
                'Nama Project',
                'Status',
                'Progress (%)',
                'Total Task',
                'Selesai',
                'Terbuka',
                'Terlambat',
                'Due Date',
                'Departemen',
                'Project Manager',
            ]);

            foreach ($data['projectReports'] as $row) {
                fputcsv($handle, [
                    $row['code'],
                    $row['name'],
                    $row['status_label'],
                    $row['progress'],
                    $row['tasks_total'],
                    $row['tasks_done'],
                    $row['tasks_open'],
                    $row['tasks_overdue'],
                    $row['due_date'] ?? '',
                    $row['department'] ?? '',
                    $row['manager']['name'] ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportTasksCsv(User $user, ?int $projectId = null): StreamedResponse
    {
        $tasks = $this->reportService->exportableTasks($user, $projectId);
        $filename = 'mathpro-laporan-task-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($tasks) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'Kode Task',
                'Judul',
                'Project',
                'Status',
                'Prioritas',
                'Assignee',
                'Due Date',
            ]);

            foreach ($tasks as $row) {
                fputcsv($handle, [
                    $row['task_number'],
                    $row['title'],
                    $row['project_code'],
                    $row['status_label'],
                    $row['priority_label'],
                    $row['assignee_name'] ?? 'Unassigned',
                    $row['due_date'] ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
