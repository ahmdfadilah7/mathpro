<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Task $task,
        private readonly User $assignedBy
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

        return (new MailMessage)
            ->subject('Task baru ditugaskan — '.$this->task->title)
            ->greeting('Halo '.$notifiable->name.',')
            ->line($this->assignedBy->name.' menetapkan Anda sebagai assignee task berikut:')
            ->line($this->task->title)
            ->line($project ? 'Project: '.$project->code.' — '.$project->name : '')
            ->line($this->task->due_date ? 'Jatuh tempo: '.$this->task->due_date->format('d M Y') : '')
            ->action('Lihat task', $url)
            ->line('Email ini dikirim otomatis oleh MathPro.');
    }
}
