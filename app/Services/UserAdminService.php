<?php

namespace App\Services;

use App\Http\Resources\UserAdminResource;
use App\Models\Department;
use App\Models\Division;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;

class UserAdminService
{
    private const PER_PAGE = 12;

    /** @return array<string, mixed> */
    public function getIndexData(array $filters): array
    {
        $filters = $this->normalizeFilters($filters);

        $users = User::query()
            ->with(['role:id,name,slug', 'department:id,name,code,division_id', 'department.division:id,name'])
            ->filter($filters)
            ->orderBy('name')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return [
            'users' => UserAdminResource::collection($users),
            'filters' => $filters,
            'stats' => [
                'total' => User::count(),
                'active' => User::where('is_active', true)->count(),
                'inactive' => User::where('is_active', false)->count(),
            ],
            'filterOptions' => $this->getFilterOptions(),
        ];
    }

    /** @return array<string, mixed> */
    public function getFormOptions(): array
    {
        return [
            'roles' => Role::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'slug'])
                ->map(fn (Role $r) => [
                    'id' => $r->id,
                    'name' => $r->name,
                    'slug' => $r->slug,
                ])
                ->values()
                ->all(),
            'divisions' => Division::query()
                ->where('is_active', true)
                ->with(['departments' => fn ($q) => $q->where('is_active', true)->select('id', 'division_id', 'name', 'code')])
                ->orderBy('name')
                ->get(['id', 'name', 'code'])
                ->map(fn (Division $d) => [
                    'id' => $d->id,
                    'name' => $d->name,
                    'code' => $d->code,
                    'departments' => $d->departments->map(fn (Department $dept) => [
                        'id' => $dept->id,
                        'name' => $dept->name,
                        'code' => $dept->code,
                    ])->values(),
                ])
                ->values()
                ->all(),
        ];
    }

    /** @param  array<string, mixed>  $data */
    public function create(array $data): User
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role_id' => $data['role_id'],
            'department_id' => $data['department_id'] ?? null,
            'position' => $data['position'] ?? null,
            'phone' => $data['phone'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? true),
            'email_verified_at' => now(),
        ]);
    }

    /** @param  array<string, mixed>  $data */
    public function update(User $user, array $data): User
    {
        $payload = Arr::only($data, [
            'name',
            'email',
            'role_id',
            'department_id',
            'position',
            'phone',
            'is_active',
        ]);

        $payload['department_id'] = $payload['department_id'] ?: null;
        $payload['is_active'] = (bool) ($payload['is_active'] ?? true);

        if (! empty($data['password'])) {
            $payload['password'] = $data['password'];
        }

        $user->update($payload);

        return $user->fresh(['role:id,name,slug', 'department:id,name,code,division_id', 'department.division:id,name']);
    }

    public function delete(User $actor, User $user): void
    {
        abort_if($actor->id === $user->id, 422, 'Anda tidak dapat menghapus akun sendiri.');

        if ($user->role?->slug === 'super-admin') {
            $superAdminCount = User::query()
                ->whereHas('role', fn ($q) => $q->where('slug', 'super-admin'))
                ->where('is_active', true)
                ->count();

            abort_if($superAdminCount <= 1, 422, 'Minimal harus ada satu Super Admin aktif.');
        }

        $user->delete();
    }

    /** @param  array<string, mixed>  $filters */
    private function normalizeFilters(array $filters): array
    {
        return [
            'search' => trim((string) ($filters['search'] ?? '')),
            'role_id' => filled($filters['role_id'] ?? null) ? (int) $filters['role_id'] : null,
            'department_id' => filled($filters['department_id'] ?? null) ? (int) $filters['department_id'] : null,
            'division_id' => filled($filters['division_id'] ?? null) ? (int) $filters['division_id'] : null,
            'is_active' => ($filters['is_active'] ?? '') === '' ? '' : (string) $filters['is_active'],
        ];
    }

    /** @return array<string, mixed> */
    private function getFilterOptions(): array
    {
        return [
            'roles' => Role::query()->orderBy('name')->get(['id', 'name']),
            'divisions' => Division::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'departments' => Department::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'division_id']),
        ];
    }
}
