<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Support\ActivityLogger;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/Profile', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        ActivityLogger::record(
            actor: $user,
            event: 'settings.profile-updated',
            description: 'Updated profile settings.',
            subject: $user,
            properties: [
                'email_changed' => $user->wasChanged('email'),
                'name_changed' => $user->wasChanged('name'),
                'locale' => $user->locale,
            ],
            request: $request,
        );

        return to_route('profile.edit');
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(ProfileDeleteRequest $request): RedirectResponse
    {
        $user = $request->user();

        abort_if(
            $user->isProtectedDemoPersona() || $user->hasBusinessIdentity() || $user->hasTechnicalIdentity(),
            403,
            'Protected demonstration and appointed Sunday School accounts cannot be self-deleted.'
        );

        ActivityLogger::record(
            actor: $user,
            event: 'settings.profile-deleted',
            description: 'Deleted the signed-in profile.',
            subject: $user,
            request: $request,
        );

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
