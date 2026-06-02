@php
    $book = $editBook ?? null;
@endphp

<h2>{{ $book ? 'Edit Buku' : 'Tambah Buku' }}</h2>
<form class="stack-form" method="post" action="{{ route('books.store') }}">
    @csrf
    <input type="hidden" name="id" value="{{ $book ? App\Support\Shelfy::id($book) : '' }}">

    <label>Judul Buku
        <input name="judul" value="{{ old('judul', $book?->judul) }}" required>
        @error('judul') <span class="form-error">{{ $message }}</span> @enderror
    </label>
    <label>Penulis
        <input name="penulis" value="{{ old('penulis', $book?->penulis) }}" required>
        @error('penulis') <span class="form-error">{{ $message }}</span> @enderror
    </label>
    <label>Kategori
        <select name="kategori" required>
            <option value="">Pilih kategori</option>
            @foreach ($categories as $category)
                <option value="{{ $category }}" @selected(old('kategori', $book?->kategori) === $category)>{{ $category }}</option>
            @endforeach
        </select>
        @error('kategori') <span class="form-error">{{ $message }}</span> @enderror
    </label>
    <label>Penerbit
        <input name="penerbit" value="{{ old('penerbit', $book?->penerbit) }}">
    </label>
    <label>Tahun Terbit
        <input type="number" min="0" name="tahun" value="{{ old('tahun', $book?->tahun) }}">
    </label>
    <label>Stok Total
        <input type="number" min="0" name="stok_total" value="{{ old('stok_total', $book?->stok_total ?? 1) }}" required>
        @error('stok_total') <span class="form-error">{{ $message }}</span> @enderror
    </label>
    <label>ISBN
        <input name="isbn" value="{{ old('isbn', $book?->isbn) }}">
    </label>
    <label>Deskripsi
        <textarea name="deskripsi" rows="4">{{ old('deskripsi', $book?->deskripsi) }}</textarea>
    </label>
    <div class="form-actions">
        @if ($book)
            <a class="button" href="{{ route('books.index') }}">Batal</a>
        @endif
        <button class="button primary" type="submit">Simpan</button>
    </div>
</form>
