<?php

namespace App\Http\Resources;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Project */
class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_color' => $this->status->color(),
            'priority' => $this->priority->value,
            'priority_label' => $this->priority->label(),
            'priority_color' => $this->priority->color(),
            'progress' => $this->progress,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'start_date_formatted' => $this->start_date?->format('d M Y'),
            'due_date' => $this->due_date?->format('Y-m-d'),
            'due_date_formatted' => $this->due_date?->format('d M Y'),
            'is_overdue' => $this->due_date?->isPast()
                && $this->status !== ProjectStatus::Completed
                && $this->status !== ProjectStatus::Cancelled,
            'budget' => $this->budget,
            'budget_formatted' => $this->budget
                ? 'Rp '.number_format((float) $this->budget, 0, ',', '.')
                : null,
            'color' => $this->color,
            'division_id' => $this->division_id,
            'department_id' => $this->department_id,
            'manager_id' => $this->manager_id,
            'division' => $this->whenLoaded('division', fn () => [
                'id' => $this->division->id,
                'name' => $this->division->name,
                'code' => $this->division->code,
            ]),
            'department' => $this->whenLoaded('department', fn () => [
                'id' => $this->department->id,
                'name' => $this->department->name,
                'code' => $this->department->code,
                'division_id' => $this->department->division_id,
            ]),
            'manager' => $this->whenLoaded('manager', fn () => $this->manager?->toBrief()),
            'tasks_count' => $this->whenCounted('tasks'),
            'tasks_done_count' => $this->when(
                isset($this->tasks_done_count),
                fn () => $this->tasks_done_count
            ),
            'created_at' => $this->created_at?->format('d M Y'),
            'updated_at' => $this->updated_at?->diffForHumans(),
        ];
    }
}
