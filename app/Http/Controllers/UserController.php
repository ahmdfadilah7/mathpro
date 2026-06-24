<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserAdminResource;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\UserAdminService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(
        private readonly UserAdminService $userAdminService,
        private readonly ActivityLogService $activityLogService
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Users/Index', $this->userAdminService->getIndexData(
            $request->only(['search', 'role_id', 'department_id', 'division_id', 'is_active'])
        ));
    }

    public function create(): Response
    {
        return Inertia::render('Users/Create', [
            'formOptions' => $this->userAdminService->getFormOptions(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = $this->userAdminService->create($request->validated());

        $this->activityLogService->log(
            $request->user(),
            'created',
            $user,
            "Membuat akun user \"{$user->name}\""
        );

        return redirect()
            ->route('users.index')
            ->with('swal', [
                'title' => 'Berhasil!',
                'message' => "User \"{$user->name}\" berhasil dibuat.",
            ]);
    }

    public function edit(User $user): Response
    {
        $user->load(['role:id,name,slug', 'department:id,name,code,division_id', 'department.division:id,name']);

        return Inertia::render('Users/Edit', [
            'user' => (new UserAdminResource($user))->resolve(),
            'formOptions' => $this->userAdminService->getFormOptions(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->userAdminService->update($user, $request->validated());

        $this->activityLogService->log(
            $request->user(),
            'updated',
            $user,
            "Memperbarui akun user \"{$user->name}\""
        );

        return redirect()
            ->route('users.index')
            ->with('swal', [
                'title' => 'Berhasil!',
                'message' => 'User berhasil diperbarui.',
            ]);
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->id === $user->id) {
            return redirect()
                ->route('users.index')
                ->with('error', 'Anda tidak dapat menghapus akun sendiri.');
        }

        if ($user->role?->slug === 'super-admin') {
            $superAdminCount = User::query()
                ->whereHas('role', fn ($q) => $q->where('slug', 'super-admin'))
                ->where('is_active', true)
                ->count();

            if ($superAdminCount <= 1) {
                return redirect()
                    ->route('users.index')
                    ->with('error', 'Minimal harus ada satu Super Admin aktif.');
            }
        }

        $name = $user->name;
        $this->activityLogService->log(
            $request->user(),
            'deleted',
            null,
            "Menghapus akun user \"{$name}\"",
            ['user_name' => $name]
        );
        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('swal', [
                'title' => 'Berhasil!',
                'message' => "User \"{$name}\" berhasil dihapus.",
            ]);
    }
}
