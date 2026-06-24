<?php

namespace App\Http\Requests\Role;

use App\Models\Role;
use App\Services\RoleService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role?->slug === 'super-admin';
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $permissionKeys = array_column(RoleService::permissionOptions(), 'key');
        /** @var Role $role */
        $role = $this->route('role');
        $isSystem = app(RoleService::class)->isSystemRole($role);

        return [
            'name' => ['required', 'string', 'max:100'],
            'slug' => $isSystem
                ? ['prohibited']
                : ['required', 'string', 'max:100', 'alpha_dash', Rule::unique(Role::class)->ignore($role->id)],
            'description' => ['nullable', 'string', 'max:500'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in($permissionKeys)],
            'is_active' => ['boolean'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'name' => 'nama role',
            'slug' => 'slug',
        ];
    }
}
