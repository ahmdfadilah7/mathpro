<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Database\Seeder;

class ActivityLogSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(ActivityLogService::class);
        $admin = User::where('email', 'admin@mathpro.test')->first();

        if (! $admin) {
            return;
        }

        $project = Project::query()->orderBy('id')->first();
        $task = Task::query()->whereNotNull('assignee_id')->orderBy('id')->first();

        if ($project) {
            $service->log(
                $admin,
                'updated',
                $project,
                "Memperbarui project \"{$project->name}\" ({$project->code})",
                $service->projectContext($project)
            );
        }

        if ($task) {
            $service->log(
                $admin,
                'status_changed',
                $task,
                "Mengubah status task \"{$task->title}\" menjadi {$task->status->label()}",
                array_merge($service->taskContext($task), [
                    'from_status' => 'todo',
                    'to_status' => $task->status->value,
                ])
            );
        }

        ActivityLog::query()->update(['created_at' => now()->subHours(2), 'updated_at' => now()->subHours(2)]);
    }
}
