<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttachmentService
{
    public const MAX_FILES = 5;

    public const MAX_FILE_KB = 10240;

    /** @var list<string> */
    public const ALLOWED_MIMES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain',
        'application/zip',
        'application/x-zip-compressed',
    ];

    /**
     * @param  list<UploadedFile>  $files
     * @return Collection<int, Attachment>
     */
    public function storeMany(Model $parent, User $user, array $files, string $directory): Collection
    {
        $stored = collect();

        foreach (array_slice($files, 0, self::MAX_FILES) as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }

            $path = $file->store($directory, 'public');

            $stored->push($parent->attachments()->create([
                'user_id' => $user->id,
                'disk' => 'public',
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]));
        }

        return $stored;
    }

    public function canDownload(User $user, Attachment $attachment): bool
    {
        $attachable = $attachment->attachable;

        if ($attachable instanceof \App\Models\Message) {
            $attachable->loadMissing('conversation.project');

            return app(ProjectAccessService::class)->isMemberOrManager(
                $user,
                $attachable->conversation->project
            );
        }

        if ($attachable instanceof \App\Models\TaskComment) {
            $attachable->loadMissing('task.project');

            return app(ProjectAccessService::class)->canView(
                $user,
                $attachable->task->project
            );
        }

        return false;
    }

    public function assetUrl(Attachment $attachment): string
    {
        if ($attachment->disk === 'public') {
            return Storage::disk('public')->url($attachment->path);
        }

        return route('attachments.download', $attachment);
    }

    public function downloadUrl(Attachment $attachment): string
    {
        return $this->assetUrl($attachment);
    }

    public function deleteAllFor(Model $parent): void
    {
        $parent->attachments()->each(function (Attachment $attachment) {
            Storage::disk($attachment->disk)->delete($attachment->path);
            $attachment->delete();
        });
    }

    public function humanSize(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }

        if ($bytes < 1024 * 1024) {
            return round($bytes / 1024, 1).' KB';
        }

        return round($bytes / (1024 * 1024), 1).' MB';
    }
}
