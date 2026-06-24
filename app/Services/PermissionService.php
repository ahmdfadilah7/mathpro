<?php

namespace App\Services;

use App\Models\Project;
use App\Models\User;

class PermissionService
{
    public function has(User $user, string $permission): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        $permissions = $user->role?->permissions ?? [];

        return in_array($permission, $permissions, true);
    }

    /** @param  list<string>  $permissions */
    public function any(User $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->has($user, $permission)) {
                return true;
            }
        }

        return false;
    }

    public function isSuperAdmin(User $user): bool
    {
        return $user->role?->slug === 'super-admin';
    }

    public function canAssignTasksAnywhere(User $user): bool
    {
        $access = app(ProjectAccessService::class);

        return Project::query()
            ->whereUserIsMemberOrManager($user)
            ->get(['id', 'manager_id'])
            ->contains(fn (Project $project) => $access->canAssignTask($user, $project));
    }

    /** @return array<string, bool|int> */
    public function abilitiesFor(User $user): array
    {
        return [
            'is_super_admin' => $this->isSuperAdmin($user),
            'can_view_projects' => $this->has($user, 'projects.view'),
            'can_manage_projects' => $this->has($user, 'projects.manage'),
            'can_manage_tasks' => $this->has($user, 'tasks.manage'),
            'can_view_reports' => $this->has($user, 'reports.view'),
            'can_assign_tasks' => $this->canAssignTasksAnywhere($user),
            'managed_projects_count' => Project::query()
                ->where('manager_id', $user->id)
                ->count(),
        ];
    }

    public function authorize(User $user, string $permission): void
    {
        abort_unless(
            $this->has($user, $permission),
            403,
            'Anda tidak memiliki izin untuk melakukan aksi ini.'
        );
    }
}
