<x-guest-layout>
    <section class="auth-card">
        <div class="auth-copy">
            <h1>Login SHELFY</h1>
            <p>Masuk sebagai admin atau mahasiswa untuk mengakses aplikasi perpustakaan digital berbasis MongoDB.</p>
        </div>

        <x-auth-session-status class="alert success" :status="session('status')" />

        <form class="stack-form" method="POST" action="{{ route('login') }}">
            @csrf

            <label>Email
                <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="Email Anda">
                @error('email')
                    <span class="form-error">{{ $message }}</span>
                @enderror
            </label>

            <label>Password
                <input type="password" name="password" required autocomplete="current-password" placeholder="Masukkan password">
                @error('password')
                    <span class="form-error">{{ $message }}</span>
                @enderror
            </label>

            <label class="inline-check">
                <input type="checkbox" name="remember">
                <span>Ingat saya</span>
            </label>

            <button class="button primary full" type="submit">Login</button>
        </form>

        <p class="auth-switch">Belum punya akun mahasiswa? <a href="{{ route('register') }}">Register di sini</a></p>
    </section>
</x-guest-layout>
