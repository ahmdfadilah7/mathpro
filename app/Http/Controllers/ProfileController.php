<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Services\ActivityLogService;
use App\Services\ProfileAvatarService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function __construct(
        private readonly ProfileAvatarService $profileAvatarService,
        private readonly ActivityLogService $activityLogService
    ) {}

    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->safe()->only(['name', 'email']);
        $avatarChanged = false;

        if ($request->boolean('remove_avatar')) {
            $this->profileAvatarService->remove($user);
            $avatarChanged = true;
        }

        if ($request->hasFile('avatar')) {
            $this->profileAvatarService->store($user, $request->file('avatar'));
            $avatarChanged = true;
        }

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $profileChanged = $user->isDirty(['name', 'email']);
        $user->save();

        if ($avatarChanged) {
            $this->activityLogService->log(
                $user,
                'avatar_updated',
                $user,
                'Memperbarui foto profil',
                [],
                $request
            );
        }

        if ($profileChanged) {
            $this->activityLogService->log(
                $user,
                'profile_updated',
                $user,
                'Memperbarui informasi profil',
                [],
                $request
            );
        }

        return Redirect::route('profile.edit')->with('swal', [
            'title' => 'Berhasil!',
            'message' => 'Profil berhasil diperbarui.',
            'redirect' => route('profile.edit'),
        ]);
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
