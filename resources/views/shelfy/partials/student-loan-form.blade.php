@php use App\Support\Shelfy; @endphp

<h2>Ajukan Peminjaman</h2>
<form class="stack-form" method="post" action="{{ route('student.loans.store') }}">
    @csrf

    <label>Buku
        <select name="book_id" required>
            <option value="">Pilih buku tersedia</option>
            @foreach ($availableBooks as $book)
                <option value="{{ Shelfy::id($book) }}">{{ $book->judul }} - stok {{ $book->stok_tersedia }}</option>
            @endforeach
        </select>
        @error('book_id') <span class="form-error">{{ $message }}</span> @enderror
    </label>
    <label>Tanggal Pinjam
        <input type="date" name="tanggal_pinjam" value="{{ old('tanggal_pinjam', date('Y-m-d')) }}" required>
    </label>
    <label>Jatuh Tempo
        <input type="date" name="tanggal_jatuh_tempo" value="{{ old('tanggal_jatuh_tempo', now()->addDays(7)->format('Y-m-d')) }}" required>
    </label>
    <label>Catatan
        <textarea name="catatan" rows="3" placeholder="Opsional">{{ old('catatan') }}</textarea>
    </label>
    <button class="button primary" type="submit">Ajukan Peminjaman</button>
</form>
