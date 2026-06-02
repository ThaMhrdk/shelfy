<form class="filters" method="get" action="{{ $filterRoute ?? route('loans.index') }}">
    <input name="q" value="{{ request('q') }}" placeholder="Cari buku atau anggota...">
    <select name="status">
        <option value="">Semua Status</option>
        <option value="dipinjam" @selected(request('status') === 'dipinjam')>Dipinjam</option>
        <option value="terlambat" @selected(request('status') === 'terlambat')>Terlambat</option>
        <option value="dikembalikan" @selected(request('status') === 'dikembalikan')>Dikembalikan</option>
    </select>
    <button class="button primary" type="submit">Filter</button>
    <a class="button" href="{{ $filterRoute ?? route('loans.index') }}">Reset</a>
</form>
