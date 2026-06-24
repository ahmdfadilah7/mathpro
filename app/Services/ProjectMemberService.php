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
                ->map(function (array $row) {
                    $userId = (int) $row['user_id'];
                    $access = (string) $row['access'];

                    $memberUser = User::query()
                        ->with('role:id,slug')
                        ->find($userId);

                    if ($memberUser?->role?->slug === 'team-lead'
                        && $access !== ProjectMemberAccess::Admin->value
                    ) {
                        $access = ProjectMemberAccess::Admin->value;
                    }

                    return [
                        'user_id' => $userId,
                        'access' => $access,
                    ];
                })
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
