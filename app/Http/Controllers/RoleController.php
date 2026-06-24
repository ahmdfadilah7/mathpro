<?php

namespace App\Http\Controllers;

use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use App\Services\ActivityLogService;
use App\Services\RoleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    public function __construct(
        private readonly RoleService $roleService,
        private readonly ActivityLogService $activityLogService
    ) {}

    public function index(): Response
    {
        return Inertia::render('Roles/Index', $this->roleService->getIndexData());
    }

    public function create(): Response
    {
        return Inertia::render('Roles/Create', [
            'formOptions' => $this->roleService->getFormOptions(),
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $role = $this->roleService->create($request->validated());

        $this->activityLogService->log(
            $request->user(),
            'created',
            $role,
            "Membuat role \"{$role->name}\""
        );

        return redirect()
            ->route('roles.index')
            ->with('swal', [
                'title' => 'Berhasil!',
                'message' => "Role \"{$role->name}\" berhasil dibuat.",
            ]);
    }

    public function edit(Role $role): Response
    {
        $role->loadCount('users');

        return Inertia::render('Roles/Edit', [
            'role' => (new RoleResource($role))->resolve(),
            'formOptions' => $this->roleService->getFormOptions(),
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $this->roleService->update($role, $request->validated());

        $this->activityLogService->log(
            $request->user(),
            'updated',
            $role,
            "Memperbarui role \"{$role->name}\""
        );

        return redirect()
            ->route('roles.index')
            ->with('swal', [
                'title' => 'Berhasil!',
                'message' => 'Role berhasil diperbarui.',
            ]);
    }

    public function destroy(Request $request, Role $role): RedirectResponse
    {
        if ($this->roleService->isSystemRole($role)) {
            return redirect()
                ->route('roles.index')
                ->with('error', 'Role sistem tidak dapat dihapus.');
        }

        if ($role->users()->exists()) {
            return redirect()
                ->route('roles.index')
                ->with('error', 'Role masih digunakan oleh user.');
        }

        $name = $role->name;
        $this->activityLogService->log(
            $request->user(),
            'deleted',
            null,
            "Menghapus role \"{$name}\"",
            ['role_name' => $name]
        );
        $role->delete();

        return redirect()
            ->route('roles.index')
            ->with('swal', [
                'title' => 'Berhasil!',
                'message' => "Role \"{$name}\" berhasil dihapus.",
            ]);
    }
}
