<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\NotificationService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationDismissalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_user_can_dismiss_notification(): void
    {
        $user = User::query()->where('email', 'manager@mathpro.test')->firstOrFail();
        $service = app(NotificationService::class);

        $before = $service->forUser($user);
        $this->assertNotEmpty($before);

        $key = $before[0]['id'];

        $this->actingAs($user)
            ->post(route('navbar.notifications.dismiss'), [
                'notification_key' => $key,
            ])
            ->assertRedirect();

        $after = $service->forUser($user);

        $this->assertFalse(collect($after)->contains('id', $key));
    }

    public function test_user_can_dismiss_all_notifications(): void
    {
        $user = User::query()->where('email', 'manager@mathpro.test')->firstOrFail();
        $service = app(NotificationService::class);

        $this->assertNotEmpty($service->forUser($user));

        $this->actingAs($user)
            ->post(route('navbar.notifications.dismiss-all'))
            ->assertRedirect();

        $this->assertEmpty($service->forUser($user));
    }
}
