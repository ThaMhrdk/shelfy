@php use App\Support\Shelfy; @endphp

<div class="table-wrap">
    <table>
        <thead>
        <tr>
            <th>Judul</th>
            <th>Penulis</th>
            <th>Kategori</th>
            <th>Stok</th>
            <th>Status</th>
            <th>Aksi</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($books as $book)
            @php
                $status = $book->status();
                $id = Shelfy::id($book);
            @endphp
            <tr>
                <td>
                    <strong>{{ $book->judul }}</strong>
                    <small>{{ $book->isbn ?: 'ISBN belum diisi' }}</small>
                </td>
                <td>{{ $book->penulis }}</td>
                <td>{{ $book->kategori }}</td>
                <td>{{ Shelfy::moneyless($book->stok_tersedia) }} / {{ Shelfy::moneyless($book->stok_total) }}</td>
                <td><span class="badge {{ Shelfy::statusClass($status) }}">{{ Shelfy::statusLabel($status) }}</span></td>
                <td class="actions">
                    <a class="icon-button" title="Edit buku" href="{{ route('books.index', ['edit' => $id]) }}#book-form">
                        <svg viewBox="0 0 24 24"><path d="M4 20h4l11-11a2.8 2.8 0 0 0-4-4L4 16v4Z"/><path d="m13.5 6.5 4 4"/></svg>
                    </a>
                    <form method="post" action="{{ route('books.destroy', $id) }}" onsubmit="return confirm('Hapus buku ini?')">
                        @csrf
                        @method('delete')
                        <button class="icon-button danger" title="Hapus buku" type="submit">
                            <svg viewBox="0 0 24 24"><path d="M4 7h16"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M6 7l1 13h10l1-13"/><path d="M9 7V4h6v3"/></svg>
                        </button>
                    </form>
                </td>
            </tr>
        @endforeach
        @if ($books->isEmpty())
            <tr><td colspan="6" class="empty">Belum ada data buku. Tambahkan data atau jalankan seed.</td></tr>
        @endif
        </tbody>
    </table>
</div>
