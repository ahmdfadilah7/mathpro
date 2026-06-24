<?php

namespace App\Http\Resources;

use App\Models\Role;
use App\Services\RoleService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Role */
class RoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $service = app(RoleService::class);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'permissions' => $this->permissions ?? [],
            'is_active' => $this->is_active,
            'is_system' => $service->isSystemRole($this->resource),
            'users_count' => $this->users_count ?? $this->users()->count(),
            'created_at_label' => $this->created_at?->format('d M Y'),
        ];
    }
}
