<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $roles = Role::query()->pluck('id', 'slug');
        $departments = Department::query()->pluck('id', 'code');

        $accounts = [
            [
                'name' => 'Ahmad Rizki',
                'email' => 'admin@mathpro.test',
                'slug' => 'super-admin',
                'department' => 'DEV',
                'position' => 'IT Director',
            ],
            [
                'name' => 'Sarah Wijaya',
                'email' => 'manager@mathpro.test',
                'slug' => 'project-manager',
                'department' => 'DEV',
                'position' => 'Project Manager',
            ],
            [
                'name' => 'Diana Putri',
                'email' => 'diana@mathpro.test',
                'slug' => 'project-manager',
                'department' => 'DIGITAL',
                'position' => 'Project Manager Marketing',
            ],
            [
                'name' => 'Rio Pratama',
                'email' => 'rio@mathpro.test',
                'slug' => 'team-lead',
                'department' => 'DEV',
                'position' => 'Tech Lead',
            ],
            [
                'name' => 'Budi Santoso',
                'email' => 'member@mathpro.test',
                'slug' => 'member',
                'department' => 'QA',
                'position' => 'QA Engineer',
            ],
            [
                'name' => 'Siti Aminah',
                'email' => 'siti@mathpro.test',
                'slug' => 'member',
                'department' => 'DEV',
                'position' => 'Frontend Developer',
            ],
            [
                'name' => 'Agus Hermawan',
                'email' => 'agus@mathpro.test',
                'slug' => 'member',
                'department' => 'INFRA',
                'position' => 'DevOps Engineer',
            ],
        ];

        foreach ($accounts as $data) {
            User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make('password'),
                'role_id' => $roles[$data['slug']],
                'department_id' => $departments[$data['department']],
                'position' => $data['position'],
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
        }
    }
}
