<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_member_cannot_access_project_create_page(): void
    {
        $user = User::query()->where('email', 'member@mathpro.test')->firstOrFail();

        $this->actingAs($user)
            ->get(route('projects.create'))
            ->assertForbidden();
    }

    public function test_manager_can_access_project_create_page(): void
    {
        $user = User::query()->where('email', 'manager@mathpro.test')->firstOrFail();

        $this->actingAs($user)
            ->get(route('projects.create'))
            ->assertOk();
    }

    public function test_member_cannot_store_new_project(): void
    {
        $user = User::query()->where('email', 'member@mathpro.test')->firstOrFail();

        $this->actingAs($user)
            ->post(route('projects.store'), $this->validProjectPayload())
            ->assertForbidden();
    }

    public function test_diana_cannot_view_erp_project(): void
    {
        $diana = User::query()->where('email', 'diana@mathpro.test')->firstOrFail();
        $erp = \App\Models\Project::query()->where('code', 'PRJ-ERP-001')->firstOrFail();

        $this->actingAs($diana)
            ->get(route('projects.show', $erp))
            ->assertForbidden();
    }

    public function test_member_cannot_access_reports(): void
    {
        $user = User::query()->where('email', 'member@mathpro.test')->firstOrFail();

        $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertForbidden();
    }

    public function test_manager_can_access_reports(): void
    {
        $user = User::query()->where('email', 'manager@mathpro.test')->firstOrFail();

        $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertOk();
    }

    public function test_super_admin_can_access_documentation(): void
    {
        $user = User::query()->where('email', 'admin@mathpro.test')->firstOrFail();

        $this->actingAs($user)
            ->get(route('documentation.index'))
            ->assertOk();
    }

    public function test_member_cannot_access_documentation(): void
    {
        $user = User::query()->where('email', 'member@mathpro.test')->firstOrFail();

        $this->actingAs($user)
            ->get(route('documentation.index'))
            ->assertForbidden();
    }

    /** @return array<string, mixed> */
    private function validProjectPayload(): array
    {
        $department = \App\Models\Department::query()->firstOrFail();

        return [
            'name' => 'Project Uji',
            'code' => 'PRJ-TEST-99',
            'description' => 'Test',
            'division_id' => $department->division_id,
            'department_id' => $department->id,
            'manager_id' => null,
            'status' => 'planning',
            'priority' => 'medium',
            'progress' => 0,
            'color' => '#6366f1',
        ];
    }
}
