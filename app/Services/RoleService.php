<?php

namespace App\Services;

use App\Http\Resources\RoleResource;
use App\Models\Role;
use Illuminate\Support\Str;

class RoleService
{
    /** @var list<string> */
    public const SYSTEM_SLUGS = [
        'super-admin',
        'project-manager',
        'team-lead',
        'member',
    ];

    /** @return list<array{key: string, label: string}> */
    public static function permissionOptions(): array
    {
        return [
            ['key' => 'projects.view', 'label' => 'Lihat project'],
            ['key' => 'projects.manage', 'label' => 'Kelola project'],
            ['key' => 'tasks.manage', 'label' => 'Kelola task'],
            ['key' => 'reports.view', 'label' => 'Lihat laporan'],
            ['key' => 'users.manage', 'label' => 'Kelola user'],
            ['key' => 'roles.manage', 'label' => 'Kelola role'],
        ];
    }

    /** @return array<string, mixed> */
    public function getIndexData(): array
    {
        $roles = Role::query()
            ->withCount('users')
            ->orderBy('name')
            ->get();

        return [
            'roles' => RoleResource::collection($roles)->resolve(),
            'permissionOptions' => self::permissionOptions(),
        ];
    }

    /** @return array<string, mixed> */
    public function getFormOptions(): array
    {
        return [
            'permissionOptions' => self::permissionOptions(),
        ];
    }

    public function isSystemRole(Role $role): bool
    {
        return in_array($role->slug, self::SYSTEM_SLUGS, true);
    }

    /** @param  array<string, mixed>  $data */
    public function create(array $data): Role
    {
        $slug = $data['slug'] ?? Str::slug($data['name']);

        return Role::create([
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'permissions' => $data['permissions'] ?? [],
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);
    }

    /** @param  array<string, mixed>  $data */
    public function update(Role $role, array $data): Role
    {
        $payload = [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'permissions' => $data['permissions'] ?? [],
            'is_active' => (bool) ($data['is_active'] ?? true),
        ];

        if (! $this->isSystemRole($role)) {
            $payload['slug'] = $data['slug'] ?? $role->slug;
        }

        $role->update($payload);

        return $role->fresh();
    }

    public function delete(Role $role): void
    {
        abort_if($this->isSystemRole($role), 422, 'Role sistem tidak dapat dihapus.');
        abort_if($role->users()->exists(), 422, 'Role masih digunakan oleh user.');
        $role->delete();
    }
}
