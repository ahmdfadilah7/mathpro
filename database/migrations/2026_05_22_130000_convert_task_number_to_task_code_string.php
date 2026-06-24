<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE tasks MODIFY task_number VARCHAR(30) NOT NULL');

        $rows = DB::table('tasks')
            ->join('projects', 'projects.id', '=', 'tasks.project_id')
            ->select('tasks.id', 'tasks.task_number', 'projects.code as project_code')
            ->orderBy('tasks.id')
            ->get();

        foreach ($rows as $row) {
            $code = preg_match('/-T\d+$/i', (string) $row->task_number)
                ? strtoupper((string) $row->task_number)
                : sprintf('%s-T%03d', $row->project_code, (int) $row->task_number);

            DB::table('tasks')->where('id', $row->id)->update(['task_number' => $code]);
        }
    }

    public function down(): void
    {
        $rows = DB::table('tasks')->select('id', 'project_id', 'task_number')->get();
        $sequence = [];

        foreach ($rows as $row) {
            if (preg_match('/-T(\d+)$/i', (string) $row->task_number, $m)) {
                $num = (int) $m[1];
            } else {
                $sequence[$row->project_id] = ($sequence[$row->project_id] ?? 0) + 1;
                $num = $sequence[$row->project_id];
            }

            DB::table('tasks')->where('id', $row->id)->update(['task_number' => (string) $num]);
        }

        DB::statement('ALTER TABLE tasks MODIFY task_number INT UNSIGNED NOT NULL');
    }
};
