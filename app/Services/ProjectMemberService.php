<?php

namespace App\Services;

use App\Enums\ProjectMemberAccess;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ProjectMemberService
{
    public function __construct(
        private readonly ActivityLogService $activityLog
    ) {}

    public function sync(Project $project, array $members, User $actor): void
    {
        DB::transaction(function () use ($project, $members, $actor) {
            $managerId = $project->manager_id;

            $normalized = collect($members)
                ->filter(fn (array $row) => filled($row['user_id'] ?? null))
                ->map(fn (array $row) => [
                    'user_id' => (int) $row['user_id'],
                    'access' => $row['access'],
                ])
                ->reject(fn (array $row) => $managerId && $row['user_id'] === $managerId)
                ->unique('user_id')
                ->values();

            $keepUserIds = $normalized->pluck('user_id')->all();

            if ($keepUserIds === []) {
                $project->members()->delete();
            } else {
                $project->members()->whereNotIn('user_id', $keepUserIds)->delete();
            }

            foreach ($normalized as $row) {
                ProjectMember::updateOrCreate(
                    [
                        'project_id' => $project->id,
                        'user_id' => $row['user_id'],
                    ],
                    [
                        'access' => ProjectMemberAccess::from($row['access']),
                        'added_by' => $actor->id,
                    ]
                );
            }

            $this->activityLog->log(
                $actor,
                'members_synced',
                $project,
                "Memperbarui anggota tim project {$project->code} ({$normalized->count()} anggota)",
                array_merge($this->activityLog->projectContext($project), [
                    'member_count' => $normalized->count(),
                ])
            );
        });
    }
}
