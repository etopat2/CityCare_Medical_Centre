<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Handles the signed-in user?s profile updates and account preferences.
 */
class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Stream the signed-in user's private profile photo.
     */
    public function photo(Request $request): BinaryFileResponse
    {
        $user = $request->user();
        abort_unless($user->avatar_path !== null && Storage::disk('local')->exists($user->avatar_path), 404);

        $disk = Storage::disk('local');

        return response()->file($disk->path($user->avatar_path), [
            'Content-Type' => $disk->mimeType($user->avatar_path) ?? 'application/octet-stream',
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();
        $avatar = $request->file('avatar');
        unset($validated['avatar']);

        $previousAvatarPath = $user->avatar_path;
        $newAvatarPath = null;

        if ($avatar !== null) {
            $newAvatarPath = $avatar->store('profile-photos/'.$user->id, 'local');

            if (! is_string($newAvatarPath)) {
                throw ValidationException::withMessages([
                    'avatar' => 'The profile photo could not be saved. Please try again.',
                ]);
            }

            $user->avatar_path = $newAvatarPath;
        }

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($newAvatarPath !== null && $previousAvatarPath !== null) {
            Storage::disk('local')->delete($previousAvatarPath);
        }

        return Redirect::route('profile.edit')->with('success', 'Profile information updated.');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        if ($user->avatar_path !== null) {
            Storage::disk('local')->delete($user->avatar_path);
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
