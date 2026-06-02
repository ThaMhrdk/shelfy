<x-guest-layout>
    <section class="auth-card wide-auth">
        <div class="auth-copy">
            <h1>Register Mahasiswa</h1>
            <p>Akun baru otomatis masuk ke role mahasiswa dan dibuatkan data anggota agar bisa meminjam buku.</p>
        </div>

        <form class="stack-form two-column-form" method="POST" action="{{ route('register') }}">
            @csrf

            <label>Nama Lengkap
                <input name="name" value="{{ old('name') }}" required autocomplete="name">
                @error('name') <span class="form-error">{{ $message }}</span> @enderror
            </label>
            <label>NIM
                <input name="nim" value="{{ old('nim') }}" required>
                @error('nim') <span class="form-error">{{ $message }}</span> @enderror
            </label>
            <label>Email
                <input type="email" name="email" value="{{ old('email') }}" required autocomplete="username">
                @error('email') <span class="form-error">{{ $message }}</span> @enderror
            </label>
            <label>Program Studi
                <input name="prodi" value="{{ old('prodi') }}">
                @error('prodi') <span class="form-error">{{ $message }}</span> @enderror
            </label>
            <label>No HP
                <input name="no_hp" value="{{ old('no_hp') }}">
                @error('no_hp') <span class="form-error">{{ $message }}</span> @enderror
            </label>
            <label>Alamat
                <input name="alamat" value="{{ old('alamat') }}">
                @error('alamat') <span class="form-error">{{ $message }}</span> @enderror
            </label>
            <label>Password
                <input type="password" name="password" required autocomplete="new-password">
                @error('password') <span class="form-error">{{ $message }}</span> @enderror
            </label>
            <label>Konfirmasi Password
                <input type="password" name="password_confirmation" required autocomplete="new-password">
                @error('password_confirmation') <span class="form-error">{{ $message }}</span> @enderror
            </label>

            <div class="form-actions span-2">
                <a class="button" href="{{ route('login') }}">Kembali Login</a>
                <button class="button primary" type="submit">Register</button>
            </div>
        </form>
    </section>
</x-guest-layout>
