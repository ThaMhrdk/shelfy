<h2>Tambah Peminjaman</h2>
<form class="stack-form" method="post" action="{{ route('loans.store') }}">
    @csrf

    <label>Buku
        <select name="book_id" required>
            <option value="">Pilih buku tersedia</option>
            @foreach ($availableBooks as $book)
                <option value="{{ App\Support\Shelfy::id($book) }}">{{ $book->judul }} - stok {{ $book->stok_tersedia }}</option>
            @endforeach
        </select>
        @error('book_id') <span class="form-error">{{ $message }}</span> @enderror
    </label>
    <label>Anggota
        <select name="member_id" required>
            <option value="">Pilih anggota aktif</option>
            @foreach ($activeMembers as $member)
                <option value="{{ App\Support\Shelfy::id($member) }}">{{ $member->nama }} - {{ $member->nim }}</option>
            @endforeach
        </select>
        @error('member_id') <span class="form-error">{{ $message }}</span> @enderror
    </label>
    <label>Tanggal Pinjam
        <input type="date" name="tanggal_pinjam" value="{{ old('tanggal_pinjam', date('Y-m-d')) }}" required>
    </label>
    <label>Jatuh Tempo
        <input type="date" name="tanggal_jatuh_tempo" value="{{ old('tanggal_jatuh_tempo', now()->addDays(7)->format('Y-m-d')) }}" required>
    </label>
    <label>Catatan
        <textarea name="catatan" rows="3">{{ old('catatan') }}</textarea>
    </label>
    <button class="button primary" type="submit">Simpan Peminjaman</button>
</form>
