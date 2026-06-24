<?php

namespace App\Http\Resources;

use App\Models\ProjectMember;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ProjectMember */
class ProjectMemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'access' => $this->access->value,
            'access_label' => $this->access->label(),
            'access_description' => $this->access->description(),
            'access_color' => $this->access->color(),
            'user' => $this->whenLoaded('user', fn () => $this->user->toBrief()),
            'added_at' => $this->created_at?->format('d M Y'),
        ];
    }
}
