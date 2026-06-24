<?php

namespace App\Http\Resources;

use App\Models\TaskComment;
use App\Services\TaskCommentService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TaskComment */
class TaskCommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'body' => $this->body,
            'is_mine' => $request->user() && (int) $this->user_id === (int) $request->user()->id,
            'can_delete' => $request->user()
                && app(TaskCommentService::class)->canDeleteComment($request->user(), $this->resource),
            'user' => $this->whenLoaded('user', fn () => $this->user->toBrief()),
            'attachments' => $this->whenLoaded(
                'attachments',
                fn () => AttachmentResource::collection($this->attachments)->resolve(),
                []
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'created_at_label' => $this->created_at?->diffForHumans(),
        ];
    }
}
