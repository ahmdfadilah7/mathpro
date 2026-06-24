<?php

namespace App\Http\Resources;

use App\Models\Attachment;
use App\Services\AttachmentService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Attachment */
class AttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $service = app(AttachmentService::class);

        return [
            'id' => $this->id,
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'size_label' => $service->humanSize((int) $this->size),
            'is_image' => str_starts_with((string) $this->mime_type, 'image/'),
            'url' => $service->assetUrl($this->resource),
            'download_url' => $service->downloadUrl($this->resource),
        ];
    }
}
