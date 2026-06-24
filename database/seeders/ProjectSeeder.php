<?php

namespace Database\Seeders;

use App\Enums\ProjectMemberAccess;
use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Department;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::query()
            ->whereIn('email', [
                'admin@mathpro.test',
                'manager@mathpro.test',
                'diana@mathpro.test',
                'rio@mathpro.test',
                'member@mathpro.test',
                'siti@mathpro.test',
                'agus@mathpro.test',
            ])
            ->get()
            ->keyBy('email');

        $admin = $users['admin@mathpro.test'];
        $sarah = $users['manager@mathpro.test'];
        $diana = $users['diana@mathpro.test'];
        $rio = $users['rio@mathpro.test'];
        $budi = $users['member@mathpro.test'];
        $siti = $users['siti@mathpro.test'];
        $agus = $users['agus@mathpro.test'];

        $dept = Department::query()->pluck('id', 'code');

        $projectsConfig = [
            [
                'name' => 'Modernisasi ERP',
                'code' => 'PRJ-ERP-001',
                'description' => 'Migrasi modul ERP ke stack baru (Laravel + Vue).',
                'department' => 'DEV',
                'manager' => $sarah,
                'status' => ProjectStatus::Active,
                'priority' => ProjectPriority::High,
                'progress' => 58,
                'color' => '#6366f1',
                'start_days_ago' => 45,
                'due_days_ahead' => 30,
                'members' => [
                    [$rio, ProjectMemberAccess::Admin],
                    [$siti, ProjectMemberAccess::Contributor],
                    [$budi, ProjectMemberAccess::Contributor],
                ],
                'tasks' => [
                    ['Integrasi modul inventory', TaskStatus::InProgress, TaskPriority::High, $siti, 5],
                    ['API payroll & absensi', TaskStatus::Review, TaskPriority::High, $rio, 3],
                    ['UAT fase 1', TaskStatus::Todo, TaskPriority::Medium, $budi, 14],
                    ['Dokumentasi deployment', TaskStatus::Todo, TaskPriority::Low, null, 21],
                    ['Refactor laporan keuangan', TaskStatus::InProgress, TaskPriority::Medium, $sarah, -2],
                ],
            ],
            [
                'name' => 'Redesign Aplikasi Mobile',
                'code' => 'PRJ-MOB-002',
                'description' => 'UI/UX baru aplikasi mobile internal.',
                'department' => 'DEV',
                'manager' => $sarah,
                'status' => ProjectStatus::Active,
                'priority' => ProjectPriority::Medium,
                'progress' => 35,
                'color' => '#8b5cf6',
                'start_days_ago' => 20,
                'due_days_ahead' => 45,
                'members' => [
                    [$siti, ProjectMemberAccess::Contributor],
                    [$rio, ProjectMemberAccess::Admin],
                ],
                'tasks' => [
                    ['Wireframe screen utama', TaskStatus::Done, TaskPriority::Medium, $siti, -10],
                    ['Implementasi dark mode', TaskStatus::InProgress, TaskPriority::Medium, $siti, 7],
                    ['Testing di perangkat Android', TaskStatus::Todo, TaskPriority::High, $budi, 12],
                    ['Review aksesibilitas', TaskStatus::Todo, TaskPriority::Low, null, 18],
                ],
            ],
            [
                'name' => 'Kampanye Digital Q2',
                'code' => 'PRJ-MKT-003',
                'description' => 'Kampanye iklan & landing page kuartal 2.',
                'department' => 'DIGITAL',
                'manager' => $diana,
                'status' => ProjectStatus::Planning,
                'priority' => ProjectPriority::Medium,
                'progress' => 12,
                'color' => '#ec4899',
                'start_days_ago' => 5,
                'due_days_ahead' => 60,
                'members' => [
                    [$siti, ProjectMemberAccess::Viewer],
                ],
                'tasks' => [
                    ['Riset keyword & persona', TaskStatus::InProgress, TaskPriority::Medium, $diana, 10],
                    ['Desain banner sosial media', TaskStatus::Todo, TaskPriority::Medium, null, 20],
                    ['Setup tracking analytics', TaskStatus::Todo, TaskPriority::Low, null, 25],
                ],
            ],
            [
                'name' => 'Migrasi Infrastruktur Cloud',
                'code' => 'PRJ-INF-004',
                'description' => 'Pindah server on-premise ke cloud.',
                'department' => 'INFRA',
                'manager' => $agus,
                'status' => ProjectStatus::OnHold,
                'priority' => ProjectPriority::Low,
                'progress' => 20,
                'color' => '#14b8a6',
                'start_days_ago' => 60,
                'due_days_ahead' => 90,
                'members' => [
                    [$rio, ProjectMemberAccess::Contributor],
                ],
                'tasks' => [
                    ['Audit server existing', TaskStatus::Done, TaskPriority::High, $agus, -30],
                    ['Terraform baseline', TaskStatus::Todo, TaskPriority::High, null, 40],
                ],
            ],
            [
                'name' => 'Portal Laporan Tahunan',
                'code' => 'PRJ-FIN-005',
                'description' => 'Portal publik laporan tahunan (selesai).',
                'department' => 'FIN',
                'manager' => $sarah,
                'status' => ProjectStatus::Completed,
                'priority' => ProjectPriority::High,
                'progress' => 100,
                'color' => '#f59e0b',
                'start_days_ago' => 120,
                'due_days_ahead' => -15,
                'members' => [
                    [$siti, ProjectMemberAccess::Contributor],
                ],
                'tasks' => [
                    ['Publish ke production', TaskStatus::Done, TaskPriority::High, $sarah, -20],
                    ['Arsip dokumentasi', TaskStatus::Done, TaskPriority::Low, $siti, -18],
                ],
            ],
        ];

        foreach ($projectsConfig as $config) {
            $department = Department::find($dept[$config['department']]);

            $project = Project::create([
                'name' => $config['name'],
                'code' => $config['code'],
                'description' => $config['description'],
                'division_id' => $department->division_id,
                'department_id' => $department->id,
                'manager_id' => $config['manager']->id,
                'status' => $config['status'],
                'priority' => $config['priority'],
                'progress' => $config['progress'],
                'start_date' => now()->subDays($config['start_days_ago']),
                'due_date' => now()->addDays($config['due_days_ahead']),
                'budget' => rand(80, 400) * 1_000_000,
                'color' => $config['color'],
            ]);

            foreach ($config['members'] as [$user, $access]) {
                ProjectMember::create([
                    'project_id' => $project->id,
                    'user_id' => $user->id,
                    'access' => $access,
                    'added_by' => $config['manager']->id,
                ]);
            }

            foreach ($config['tasks'] as $index => [$title, $status, $priority, $assignee, $dueDays]) {
                Task::create([
                    'project_id' => $project->id,
                    'assignee_id' => $assignee?->id,
                    'created_by' => $config['manager']->id,
                    'title' => $title,
                    'description' => "Task pada {$project->name}.",
                    'status' => $status,
                    'priority' => $priority,
                    'due_date' => now()->addDays($dueDays),
                    'start_date' => now()->subDays(max(1, 10 - $index)),
                    'order' => $index,
                    'estimated_hours' => rand(4, 32),
                ]);
            }
        }
    }
}
