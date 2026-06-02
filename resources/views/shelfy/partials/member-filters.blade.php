<form class="filters" method="get" action="{{ route('members.index') }}">
    <input name="q" value="{{ request('q') }}" placeholder="Cari nama, NIM, prodi, email...">
    <select name="status">
        <option value="">Semua Status</option>
        <option value="aktif" @selected(request('status') === 'aktif')>Aktif</option>
        <option value="nonaktif" @selected(request('status') === 'nonaktif')>Nonaktif</option>
    </select>
    <button class="button primary" type="submit">Filter</button>
    <a class="button" href="{{ route('members.index') }}">Reset</a>
</form>
