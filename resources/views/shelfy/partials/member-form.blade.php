@php $member = $editMember ?? null; @endphp

<h2>{{ $member ? 'Edit Anggota' : 'Tambah Anggota' }}</h2>
<form class="stack-form" method="post" action="{{ route('members.store') }}">
    @csrf
    <input type="hidden" name="id" value="{{ $member ? App\Support\Shelfy::id($member) : '' }}">

    <label>Nama Anggota
        <input name="nama" value="{{ old('nama', $member?->nama) }}" required>
        @error('nama') <span class="form-error">{{ $message }}</span> @enderror
    </label>
    <label>NIM
        <input name="nim" value="{{ old('nim', $member?->nim) }}" required>
        @error('nim') <span class="form-error">{{ $message }}</span> @enderror
    </label>
    <label>Program Studi
        <input name="prodi" value="{{ old('prodi', $member?->prodi) }}">
    </label>
    <label>Email
        <input type="email" name="email" value="{{ old('email', $member?->email) }}">
        @error('email') <span class="form-error">{{ $message }}</span> @enderror
    </label>
    <label>No HP
        <input name="no_hp" value="{{ old('no_hp', $member?->no_hp) }}">
    </label>
    <label>Alamat
        <textarea name="alamat" rows="3">{{ old('alamat', $member?->alamat) }}</textarea>
    </label>
    <label>Status
        <select name="status">
            <option value="aktif" @selected(old('status', $member?->status ?? 'aktif') === 'aktif')>Aktif</option>
            <option value="nonaktif" @selected(old('status', $member?->status) === 'nonaktif')>Nonaktif</option>
        </select>
    </label>
    <div class="form-actions">
        @if ($member)
            <a class="button" href="{{ route('members.index') }}">Batal</a>
        @endif
        <button class="button primary" type="submit">Simpan</button>
    </div>
</form>
