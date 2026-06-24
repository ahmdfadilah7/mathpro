<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserAdminResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'position' => $this->position,
            'phone' => $this->phone,
            'is_active' => $this->is_active,
            'initials' => $this->initials,
            'role' => $this->whenLoaded('role', fn () => [
                'id' => $this->role->id,
                'name' => $this->role->name,
                'slug' => $this->role->slug,
            ]),
            'department' => $this->whenLoaded('department', function () {
                if (! $this->department) {
                    return null;
                }

                return [
                    'id' => $this->department->id,
                    'name' => $this->department->name,
                    'code' => $this->department->code,
                    'division' => $this->department->relationLoaded('division') && $this->department->division
                        ? [
                            'id' => $this->department->division->id,
                            'name' => $this->department->division->name,
                        ]
                        : null,
                ];
            }),
            'created_at_label' => $this->created_at?->format('d M Y'),
        ];
    }
}
