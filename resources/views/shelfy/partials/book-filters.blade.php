<form class="filters" method="get" action="{{ $filterRoute ?? route('books.index') }}">
    <input name="q" value="{{ request('q') }}" placeholder="Cari judul, penulis, ISBN...">
    <select name="kategori">
        <option value="">Semua Kategori</option>
        @foreach ($categories as $category)
            <option value="{{ $category }}" @selected(request('kategori') === $category)>{{ $category }}</option>
        @endforeach
    </select>
    <select name="status">
        <option value="">Semua Stok</option>
        <option value="tersedia" @selected(request('status') === 'tersedia')>Tersedia</option>
        <option value="habis" @selected(request('status') === 'habis')>Habis</option>
    </select>
    <button class="button primary" type="submit">Filter</button>
    <a class="button" href="{{ $filterRoute ?? route('books.index') }}">Reset</a>
</form>
