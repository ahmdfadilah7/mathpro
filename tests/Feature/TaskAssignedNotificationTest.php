<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TaskAssignedNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_assigning_task_sends_email_to_assignee(): void
    {
        Notification::fake();

        $manager = User::query()->where('email', 'manager@mathpro.test')->firstOrFail();
        $assignee = User::query()->where('email', 'siti@mathpro.test')->firstOrFail();
        $task = Task::query()
            ->whereNull('assignee_id')
            ->whereHas('project', fn ($q) => $q->where('manager_id', $manager->id))
            ->firstOrFail();

        $this->actingAs($manager)->patch(
            route('unassigned.assign', $task),
            ['assignee_id' => $assignee->id]
        );

        Notification::assertSentTo($assignee, TaskAssignedNotification::class);
    }
}
