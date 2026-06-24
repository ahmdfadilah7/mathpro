<?php

namespace App\Http\Requests\Task;

use App\Services\AttachmentService;
use Illuminate\Foundation\Http\FormRequest;
class StoreTaskCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'body' => ['nullable', 'string', 'max:5000'],
            'attachments' => ['nullable', 'array', 'max:'.AttachmentService::MAX_FILES],
            'attachments.*' => [
                'file',
                'max:'.AttachmentService::MAX_FILE_KB,
                'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,txt,zip',
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $body = trim((string) $this->input('body'));
            $files = $this->file('attachments', []);

            if ($body === '' && (! is_array($files) || count($files) === 0)) {
                $validator->errors()->add('body', 'Komentar atau lampiran wajib diisi.');
            }
        });
    }
}
