<?php

namespace App\Http\Resources;

use App\Models\Message;
use App\Services\ChatService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Message */
class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'body' => $this->body,
            'is_mine' => $request->user() && (int) $this->user_id === (int) $request->user()->id,
            'can_delete' => $request->user()
                && app(ChatService::class)->canDeleteMessage($request->user(), $this->resource),
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
