@php use App\Support\Shelfy; @endphp
@php
    $canManageBooks = ($canManageBooks ?? ($isAdmin ?? false)) && ! ($readOnlyActions ?? false);
    $canDeleteBooks = ($canDeleteBooks ?? ($isAdmin ?? false)) && ! ($readOnlyActions ?? false);
@endphp

<div class="table-wrap">
    <table>
        <thead>
        <tr>
            <th>Buku</th>
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
                    <div class="book-cell">
                        <div class="book-cover tiny">
                            @if ($book->cover_path)
                                <img src="{{ Shelfy::fileUrl($book->cover_path) }}" alt="Cover {{ $book->judul }}">
                            @else
                                <span>{{ strtoupper(substr($book->judul, 0, 1)) }}</span>
                            @endif
                        </div>
                        <span>
                            <strong>{{ $book->judul }}</strong>
                            <small>{{ $book->isbn ?: 'ISBN belum diisi' }}</small>
                        </span>
                    </div>
                </td>
                <td>{{ $book->penulis }}</td>
                <td>{{ $book->kategori }}</td>
                <td>{{ Shelfy::moneyless($book->stok_tersedia) }} / {{ Shelfy::moneyless($book->stok_total) }}</td>
                <td><span class="badge {{ Shelfy::statusClass($status) }}">{{ Shelfy::statusLabel($status) }}</span></td>
                <td class="actions">
                    <a class="button tiny" href="{{ route('books.show', $id) }}">Detail</a>
                    @if ($canManageBooks)
                        <button class="icon-button" title="Edit buku" type="button" onclick="document.getElementById('book-edit-{{ $id }}').showModal()">
                            <svg viewBox="0 0 24 24"><path d="M4 20h4l11-11a2.8 2.8 0 0 0-4-4L4 16v4Z"/><path d="m13.5 6.5 4 4"/></svg>
                        </button>
                    @endif
                    @if ($canDeleteBooks)
                        <form method="post" action="{{ route('books.destroy', $id) }}" onsubmit="return confirm('Hapus buku ini?')">
                            @csrf
                            @method('delete')
                            <button class="icon-button danger" title="Hapus buku" type="submit">
                                <svg viewBox="0 0 24 24"><path d="M4 7h16"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M6 7l1 13h10l1-13"/><path d="M9 7V4h6v3"/></svg>
                            </button>
                        </form>
                    @endif
                </td>
            </tr>
        @endforeach
        @if ($books->isEmpty())
            <tr><td colspan="6" class="empty">Belum ada data buku. Tambahkan data atau jalankan seed.</td></tr>
        @endif
        </tbody>
    </table>
</div>
