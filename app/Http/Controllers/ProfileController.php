<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Member;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
            'member' => $request->user()->member_id ? Member::query()->find($request->user()->member_id) : null,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $payload = $request->validated();
        unset($payload['photo']);
        $payload['nama'] = $payload['name'];

        $existing = User::query()->where('email', $payload['email'])->first();
        if ($existing && (string) $existing->getKey() !== (string) $request->user()->getKey()) {
            return Redirect::route('profile.edit')
                ->withErrors(['email' => 'Email sudah dipakai akun lain.'])
                ->withInput();
        }

        $request->user()->fill($payload);

        if ($request->hasFile('photo')) {
            if ($request->user()->avatar_path) {
                Storage::disk('public')->delete((string) $request->user()->avatar_path);
            }

            $request->user()->avatar_path = $request->file('photo')->store('profile-photos', 'public');
        }

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        if ($request->user()->member_id) {
            Member::query()->where('_id', $request->user()->member_id)->update([
                'nama' => $payload['name'],
                'email' => $payload['email'],
                'prodi' => $payload['prodi'] ?? null,
                'no_hp' => $payload['no_hp'] ?? null,
                'alamat' => $payload['alamat'] ?? null,
            ]);
        }

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
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

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
