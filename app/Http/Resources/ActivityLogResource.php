<?php

namespace App\Http\Resources;

use App\Models\ActivityLog;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ActivityLog */
class ActivityLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $properties = $this->properties ?? [];

        return [
            'id' => $this->id,
            'action' => $this->action,
            'action_label' => ActivityLogService::actionLabel($this->action),
            'action_color' => ActivityLogService::actionColor($this->action),
            'description' => $this->description,
            'properties' => $properties,
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'user' => $this->whenLoaded('user', fn () => $this->user?->toBrief()),
            'created_at' => $this->created_at?->toIso8601String(),
            'created_at_label' => $this->created_at?->diffForHumans(),
            'project' => isset($properties['project_id']) ? [
                'id' => $properties['project_id'],
                'code' => $properties['project_code'] ?? null,
                'name' => $properties['project_name'] ?? null,
            ] : null,
        ];
    }
}
