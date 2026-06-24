<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    public function __construct(
        private readonly ActivityLogService $activityLogService
    ) {}

    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $user = $request->user();
        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        $this->activityLogService->log(
            $user,
            'password_changed',
            $user,
            'Mengubah password akun',
            [],
            $request
        );

        return back()->with('swal', [
            'title' => 'Berhasil!',
            'message' => 'Password berhasil diperbarui.',
            'redirect' => route('profile.edit'),
        ]);
    }
}
