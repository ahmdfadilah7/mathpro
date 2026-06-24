<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_manager_can_export_projects_csv(): void
    {
        $user = User::query()->where('email', 'manager@mathpro.test')->firstOrFail();

        $response = $this->actingAs($user)
            ->get(route('reports.export.projects'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('attachment', (string) $response->headers->get('content-disposition'));
    }

    public function test_member_cannot_export_reports(): void
    {
        $user = User::query()->where('email', 'member@mathpro.test')->firstOrFail();

        $this->actingAs($user)
            ->get(route('reports.export.projects'))
            ->assertForbidden();
    }
}
