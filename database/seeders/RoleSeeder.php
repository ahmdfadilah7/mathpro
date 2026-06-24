<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Services\RoleService;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $allPermissions = array_column(RoleService::permissionOptions(), 'key');

        $roles = [
            [
                'name' => 'Super Admin',
                'slug' => 'super-admin',
                'description' => 'Akses penuh sistem, kelola user & role',
                'permissions' => $allPermissions,
            ],
            [
                'name' => 'Project Manager',
                'slug' => 'project-manager',
                'description' => 'Mengelola project dan tim',
                'permissions' => ['projects.view', 'projects.manage', 'tasks.manage', 'reports.view'],
            ],
            [
                'name' => 'Team Lead',
                'slug' => 'team-lead',
                'description' => 'Memimpin task di departemen',
                'permissions' => ['projects.view', 'tasks.manage', 'reports.view'],
            ],
            [
                'name' => 'Member',
                'slug' => 'member',
                'description' => 'Anggota tim — My Tasks & task yang ditugaskan',
                'permissions' => ['projects.view', 'tasks.manage'],
            ],
        ];

        foreach ($roles as $data) {
            Role::create([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'description' => $data['description'],
                'permissions' => $data['permissions'],
                'is_active' => true,
            ]);
        }
    }
}
