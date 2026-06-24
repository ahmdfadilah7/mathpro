<?php

namespace App\Http\Resources;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Services\ProjectAccessService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Task */
class UnassignedTaskListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $access = app(ProjectAccessService::class);
        $project = $this->project;
        return array_merge(
            (new TaskResource($this))->resolve(),
            [
                'is_overdue' => $this->isOverdue(),
                'project' => $this->whenLoaded('project', fn () => [
                    'id' => $this->project->id,
                    'name' => $this->project->name,
                    'code' => $this->project->code,
                    'color' => $this->project->color,
                    'is_manager' => $user && (int) $this->project->manager_id === (int) $user->id,
                ]),
                'can_assign' => $user && $project
                    ? $access->canAssignTask($user, $project)
                    : false,
            ]
        );
    }

    private function isOverdue(): bool
    {
        if ($this->status === TaskStatus::Done || ! $this->due_date) {
            return false;
        }

        return $this->due_date->isPast();
    }
}
