<?php

namespace App\Services;

use App\Enums\ProjectMemberAccess;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\User;

class ProjectAccessService
{
    public const EDIT_FULL = 'full';

    public const EDIT_CONTRIBUTOR = 'contributor';

    public function isProjectManager(User $user, Project $project): bool
    {
        return $project->manager_id === $user->id;
    }

    public function isSameDivisionDepartment(User $user, Project $project): bool
    {
        if (! $user->department_id || ! $project->department_id) {
            return false;
        }

        return (int) $user->department_id === (int) $project->department_id;
    }

    public function resolveMembershipAccess(User $user, Project $project): ?ProjectMemberAccess
    {
        $membership = ProjectMember::query()
            ->where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->first();

        return $membership?->access;
    }

    public function isFullTaskManager(User $user, Project $project): bool
    {
        return $this->isSuperAdmin($user) || $this->isProjectManager($user, $project);
    }

    public function canView(User $user, Project $project): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        if ($this->isProjectManager($user, $project)) {
            return true;
        }

        if ($this->resolveMembershipAccess($user, $project) !== null) {
            return true;
        }

        return $this->isSameDivisionDepartment($user, $project);
    }

    public function canManageProject(User $user, Project $project): bool
    {
        return $this->isFullTaskManager($user, $project);
    }

    public function canManageMembers(User $user, Project $project): bool
    {
        return $this->canManageProject($user, $project);
    }

    public function isMemberOrManager(User $user, Project $project): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        if ($this->isProjectManager($user, $project)) {
            return true;
        }

        return $this->resolveMembershipAccess($user, $project) !== null;
    }

    public function canCreateTask(User $user, Project $project): bool
    {
        if ($this->isFullTaskManager($user, $project)) {
            return true;
        }

        return $this->resolveMembershipAccess($user, $project) === ProjectMemberAccess::Admin;
    }

    /** Menetapkan assignee pada task yang masih unassigned (PM / Admin anggota) */
    public function canAssignTask(User $user, Project $project): bool
    {
        if (! $this->isMemberOrManager($user, $project)) {
            return false;
        }

        if ($this->isFullTaskManager($user, $project)) {
            return true;
        }

        return $this->resolveMembershipAccess($user, $project) === ProjectMemberAccess::Admin;
    }

    public function canDeleteTask(User $user, Project $project): bool
    {
        return $this->isFullTaskManager($user, $project);
    }

    public function getTaskEditMode(User $user, Project $project, Task $task): ?string
    {
        if ($this->isFullTaskManager($user, $project)) {
            return self::EDIT_FULL;
        }

        $access = $this->resolveMembershipAccess($user, $project);

        if ($access === ProjectMemberAccess::Admin) {
            return self::EDIT_FULL;
        }

        if (
            $access === ProjectMemberAccess::Contributor
            && (int) $task->assignee_id === (int) $user->id
        ) {
            return self::EDIT_CONTRIBUTOR;
        }

        return null;
    }

    public function canUpdateTask(User $user, Project $project, Task $task): bool
    {
        return $this->getTaskEditMode($user, $project, $task) !== null;
    }

    /** Update status dari My Tasks: hanya assignee task */
    public function canUpdateMyTaskStatus(User $user, Project $project, Task $task): bool
    {
        if (! $this->canView($user, $project)) {
            return false;
        }

        return (int) $task->assignee_id === (int) $user->id;
    }

    public function canSetTaskStatusTo(User $user, Project $project, Task $task, TaskStatus $status): bool
    {
        if ($task->status === TaskStatus::Done) {
            return false;
        }

        if (! $this->canChangeTaskStatus($user, $project, $task)) {
            return false;
        }

        if ($status === TaskStatus::Done) {
            return $this->isFullTaskManager($user, $project);
        }

        return true;
    }

    /** @return list<array{value: string, label: string, color: string}> */
    public function allowedMyTaskStatusOptions(User $user, Project $project, Task $task): array
    {
        return array_values(array_filter(
            TaskStatus::options(),
            fn (array $option) => $this->canSetTaskStatusTo(
                $user,
                $project,
                $task,
                TaskStatus::from($option['value'])
            )
        ));
    }

    private function canChangeTaskStatus(User $user, Project $project, Task $task): bool
    {
        return $this->canUpdateMyTaskStatus($user, $project, $task)
            || $this->canUpdateTask($user, $project, $task);
    }

    /** @return array{can_edit: bool, can_delete: bool, edit_mode: string|null} */
    public function taskPermissionsFor(User $user, Project $project, Task $task): array
    {
        $editMode = $this->getTaskEditMode($user, $project, $task);

        return [
            'can_edit' => $editMode !== null,
            'can_delete' => $this->canDeleteTask($user, $project),
            'edit_mode' => $editMode,
        ];
    }

    /** @return list<int> */
    public function assignableUserIds(Project $project, array $extraUserIds = []): array
    {
        $ids = [];

        if ($project->manager_id) {
            $ids[] = (int) $project->manager_id;
        }

        $memberIds = ProjectMember::query()
            ->where('project_id', $project->id)
            ->whereIn('access', [
                ProjectMemberAccess::Contributor->value,
                ProjectMemberAccess::Admin->value,
            ])
            ->pluck('user_id')
            ->all();

        $ids = array_merge($ids, $memberIds, $extraUserIds);

        return array_values(array_unique(array_map('intval', array_filter($ids))));
    }

    public function isAssignable(Project $project, ?int $userId): bool
    {
        if ($userId === null) {
            return true;
        }

        return in_array($userId, $this->assignableUserIds($project), true);
    }

    /** @return array<string, mixed> */
    public function permissionsFor(User $user, Project $project): array
    {
        $isManager = $this->isProjectManager($user, $project);
        $membership = $this->resolveMembershipAccess($user, $project);
        $isDeptPeer = $this->isSameDivisionDepartment($user, $project) && ! $isManager && $membership === null;

        $accessLabel = match (true) {
            $this->isSuperAdmin($user) && ! $isManager => 'Super Admin',
            $isManager => 'Project Manager',
            $membership !== null => $membership->label(),
            $isDeptPeer => 'Tim divisi (lihat saja)',
            default => null,
        };

        return [
            'can_view' => $this->canView($user, $project),
            'can_manage_project' => $this->canManageProject($user, $project),
            'can_manage_members' => $this->canManageMembers($user, $project),
            'can_create_task' => $this->canCreateTask($user, $project),
            'can_delete_task' => $this->canDeleteTask($user, $project),
            'access' => $membership?->value,
            'access_label' => $accessLabel,
            'is_manager' => $isManager,
            'is_department_peer' => $isDeptPeer,
            'is_super_admin' => $this->isSuperAdmin($user),
        ];
    }

    public function authorizeView(User $user, Project $project): void
    {
        abort_unless($this->canView($user, $project), 403, 'Anda tidak memiliki akses ke project ini.');
    }

    public function authorizeManageProject(User $user, Project $project): void
    {
        abort_unless(
            $this->canManageProject($user, $project),
            403,
            'Hanya project manager yang dapat mengelola project ini.'
        );
    }

    public function authorizeCreateTask(User $user, Project $project): void
    {
        abort_unless(
            $this->canCreateTask($user, $project),
            403,
            'Anda tidak memiliki izin menambah task.'
        );
    }

    public function authorizeUpdateTask(User $user, Project $project, Task $task): void
    {
        abort_unless(
            $this->canUpdateTask($user, $project, $task),
            403,
            'Anda tidak memiliki izin mengubah task ini.'
        );
    }

    public function authorizeDeleteTask(User $user, Project $project): void
    {
        abort_unless(
            $this->canDeleteTask($user, $project),
            403,
            'Hanya project manager yang dapat menghapus task.'
        );
    }

    public function authorizeManageMembers(User $user, Project $project): void
    {
        abort_unless(
            $this->canManageMembers($user, $project),
            403,
            'Hanya project manager yang dapat mengelola tim & akses.'
        );
    }

    public function authorizeAssignTask(User $user, Project $project): void
    {
        abort_unless(
            $this->canAssignTask($user, $project),
            403,
            'Anda tidak memiliki izin menetapkan assignee task.'
        );
    }

    private function isSuperAdmin(User $user): bool
    {
        return $user->role?->slug === 'super-admin';
    }
}
