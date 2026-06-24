<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskDueReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Task $task,
        private readonly string $reminderType
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->task->loadMissing('project:id,name,code');

        $project = $this->task->project;
        $url = $project
            ? route('projects.show', $project).'#tasks'
            : route('my-tasks.index');

        $subject = $this->reminderType === 'overdue'
            ? 'Task terlambat — '.$this->task->title
            : 'Task jatuh tempo hari ini — '.$this->task->title;

        $intro = $this->reminderType === 'overdue'
            ? 'Task berikut sudah melewati tanggal jatuh tempo:'
            : 'Task berikut jatuh tempo hari ini:';

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Halo '.$notifiable->name.',')
            ->line($intro)
            ->line($this->task->title)
            ->line($project ? 'Project: '.$project->code : '')
            ->line($this->task->due_date ? 'Due date: '.$this->task->due_date->format('d M Y') : '')
            ->action('Buka task', $url)
            ->line('Kelola task Anda di MathPro.');
    }
}
