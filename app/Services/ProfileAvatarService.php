<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProfileAvatarService
{
    public const MAX_KB = 2048;

    /** @var list<string> */
    public const ALLOWED_MIMES = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    public function store(User $user, UploadedFile $file): string
    {
        $this->deleteStoredFile($user);

        $path = $file->store('avatars/'.$user->id, 'public');
        $user->update(['avatar' => $path]);

        return $path;
    }

    public function remove(User $user): void
    {
        $this->deleteStoredFile($user);
        $user->update(['avatar' => null]);
    }

    public function url(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    private function deleteStoredFile(User $user): void
    {
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }
    }
}
