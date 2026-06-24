<?php

namespace App\Http\Resources;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Task */
class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'task_number' => $this->task_number,
            'title' => $this->title,
            'description' => $this->description,
            'description_excerpt' => $this->descriptionExcerpt(),
            'estimated_hours_label' => $this->estimated_hours !== null
                ? rtrim(rtrim(number_format((float) $this->estimated_hours, 1, ',', '.'), '0'), ',').' jam'
                : null,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_color' => $this->status->color(),
            'priority' => $this->priority->value,
            'priority_label' => $this->priority->label(),
            'priority_color' => $this->priority->color(),
            'due_date' => $this->due_date?->format('Y-m-d'),
            'due_date_formatted' => $this->due_date?->format('d M Y'),
            'start_date' => $this->start_date?->format('Y-m-d'),
            'start_date_formatted' => $this->start_date?->format('d M Y'),
            'order' => $this->order,
            'estimated_hours' => $this->estimated_hours,
            'actual_hours' => $this->actual_hours,
            'assignee_id' => $this->assignee_id,
            'assignee' => $this->whenLoaded('assignee', fn () => $this->assignee?->toBrief()),
            'created_by' => $this->created_by,
            'creator' => $this->whenLoaded('creator', fn () => $this->creator?->toBrief()),
            'created_at' => $this->created_at?->diffForHumans(),
            'updated_at' => $this->updated_at?->diffForHumans(),
        ];
    }

    private function descriptionExcerpt(): ?string
    {
        if (! $this->description) {
            return null;
        }

        $text = trim(preg_replace('/\s+/', ' ', strip_tags($this->description)) ?? '');

        if ($text === '') {
            return null;
        }

        return mb_strlen($text) > 100
            ? mb_substr($text, 0, 100).'…'
            : $text;
    }
}
