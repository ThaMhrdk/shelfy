@php use App\Support\Shelfy; @endphp

<x-app-layout>
    <section class="page-heading">
        <div>
            <h1>Profil</h1>
            <p>UI profil untuk memenuhi tambahan dosen dan mengelola data akun login.</p>
        </div>
    </section>

    <section class="profile-grid">
        <article class="panel profile-summary">
            <span class="avatar large">
                @if ($user->avatar_path)
                    <img src="{{ Shelfy::fileUrl($user->avatar_path) }}" alt="Foto {{ $user->displayName() }}">
                @else
                    {{ Shelfy::initials($user) }}
                @endif
            </span>
            <h2>{{ $user->displayName() }}</h2>
            <p>{{ $user->email }}</p>
            <span class="badge {{ Shelfy::statusClass($user->role ?? 'mahasiswa') }}">{{ Shelfy::statusLabel($user->role ?? 'mahasiswa') }}</span>
            @if ($user->isStudent())
                <dl class="meta-list">
                    <div><dt>NIM</dt><dd>{{ $user->nim ?: '-' }}</dd></div>
                    <div><dt>Data anggota</dt><dd>{{ $member ? 'Terhubung' : 'Belum terhubung' }}</dd></div>
                </dl>
            @endif
        </article>

        <article class="panel">
            <h2>Edit Profil</h2>
            <form class="stack-form two-column-form" method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                @csrf
                @method('patch')

                <label>Nama
                    <input name="name" value="{{ old('name', $user->name) }}" required>
                    @error('name') <span class="form-error">{{ $message }}</span> @enderror
                </label>
                <label>Email
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
                    @error('email') <span class="form-error">{{ $message }}</span> @enderror
                </label>
                <label>Foto Profil
                    <input type="file" name="photo" accept="image/*">
                    <small>Foto JPG, PNG, atau WebP maksimal 2 MB.</small>
                    @error('photo') <span class="form-error">{{ $message }}</span> @enderror
                </label>
                @if ($user->isStudent())
                    <label>NIM
                        <input value="{{ $user->nim ?: '-' }}" disabled>
                    </label>
                    <label>Program Studi
                        <input name="prodi" value="{{ old('prodi', $member?->prodi ?? $user->prodi) }}">
                    </label>
                    <label>No HP
                        <input name="no_hp" value="{{ old('no_hp', $member?->no_hp ?? $user->no_hp) }}">
                    </label>
                    <label>Alamat
                        <input name="alamat" value="{{ old('alamat', $member?->alamat ?? $user->alamat) }}">
                    </label>
                @endif

                <div class="form-actions span-2">
                    <button class="button primary" type="submit">Simpan Profil</button>
                </div>
            </form>

            <div style="height: 18px"></div>

            <h2>Update Password</h2>
            <form class="stack-form two-column-form" method="post" action="{{ route('password.update') }}">
                @csrf
                @method('put')

                <label>Password Lama
                    <input type="password" name="current_password" autocomplete="current-password">
                    @if ($errors->updatePassword->get('current_password'))
                        <span class="form-error">{{ $errors->updatePassword->first('current_password') }}</span>
                    @endif
                </label>
                <label>Password Baru
                    <input type="password" name="password" autocomplete="new-password">
                    @if ($errors->updatePassword->get('password'))
                        <span class="form-error">{{ $errors->updatePassword->first('password') }}</span>
                    @endif
                </label>
                <label>Konfirmasi Password Baru
                    <input type="password" name="password_confirmation" autocomplete="new-password">
                </label>

                <div class="form-actions span-2">
                    <button class="button primary" type="submit">Simpan Password</button>
                </div>
            </form>
        </article>
    </section>
</x-app-layout>
