<?php

namespace App\Console\Commands;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Notifications\TaskDueReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendTaskReminderEmails extends Command
{
    protected $signature = 'mathpro:send-task-reminders';

    protected $description = 'Kirim email pengingat task jatuh tempo hari ini dan terlambat';

    public function handle(): int
    {
        $sent = 0;

        $overdue = Task::query()
            ->whereNot('status', TaskStatus::Done)
            ->whereDate('due_date', '<', now())
            ->whereNotNull('assignee_id')
            ->with(['assignee', 'project:id,name,code'])
            ->get();

        foreach ($overdue as $task) {
            if ($task->assignee) {
                Notification::send($task->assignee, new TaskDueReminderNotification($task, 'overdue'));
                $sent++;
            }
        }

        $dueToday = Task::query()
            ->whereNot('status', TaskStatus::Done)
            ->whereDate('due_date', now())
            ->whereNotNull('assignee_id')
            ->with(['assignee', 'project:id,name,code'])
            ->get();

        foreach ($dueToday as $task) {
            if ($task->assignee) {
                Notification::send($task->assignee, new TaskDueReminderNotification($task, 'due_today'));
                $sent++;
            }
        }

        $this->info("Pengingat email terkirim: {$sent}");

        return self::SUCCESS;
    }
}
