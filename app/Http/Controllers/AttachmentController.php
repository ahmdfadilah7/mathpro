<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Services\AttachmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    public function __construct(
        private readonly AttachmentService $attachmentService
    ) {}

    public function download(Request $request, Attachment $attachment): StreamedResponse|BinaryFileResponse
    {
        abort_unless(
            $this->attachmentService->canDownload($request->user(), $attachment),
            403
        );

        $disk = Storage::disk($attachment->disk);
        $inline = $request->boolean('inline')
            || str_starts_with((string) $attachment->mime_type, 'image/');

        if ($inline) {
            return response()->file($disk->path($attachment->path), [
                'Content-Type' => $attachment->mime_type ?: 'application/octet-stream',
                'Content-Disposition' => 'inline; filename="'.$attachment->original_name.'"',
            ]);
        }

        return $disk->download($attachment->path, $attachment->original_name);
    }
}
