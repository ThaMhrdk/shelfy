<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\User;
use App\Support\Shelfy;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nim' => ['required', 'string', 'max:100'],
            'prodi' => ['nullable', 'string', 'max:255'],
            'no_hp' => ['nullable', 'string', 'max:100'],
            'alamat' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        if (User::query()->where('email', $request->email)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'Email sudah terdaftar.',
            ]);
        }

        $member = Member::query()->updateOrCreate(
            ['nim' => $request->nim],
            [
                'nama' => $request->name,
                'prodi' => $request->prodi,
                'email' => $request->email,
                'no_hp' => $request->no_hp,
                'alamat' => $request->alamat,
                'status' => 'aktif',
            ]
        );

        $user = User::create([
            'name' => $request->name,
            'nama' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'mahasiswa',
            'nim' => $request->nim,
            'prodi' => $request->prodi,
            'no_hp' => $request->no_hp,
            'alamat' => $request->alamat,
            'member_id' => Shelfy::id($member),
            'status' => 'aktif',
        ]);

        $member->update(['user_id' => Shelfy::id($user)]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('student.dashboard', absolute: false));
    }
}
