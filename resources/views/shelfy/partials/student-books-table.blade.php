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
                $available = (int) ($book->stok_tersedia ?? 0);
                $status = $available > 0 ? 'tersedia' : 'habis';
            @endphp
            <tr>
                <td><strong>{{ $book->judul }}</strong><small>{{ $book->isbn ?: 'ISBN belum diisi' }}</small></td>
                <td>{{ $book->penulis }}</td>
                <td>{{ $book->kategori }}</td>
                <td>{{ Shelfy::moneyless($available) }} / {{ Shelfy::moneyless($book->stok_total) }}</td>
                <td><span class="badge {{ Shelfy::statusClass($status) }}">{{ Shelfy::statusLabel($status) }}</span></td>
                <td class="actions">
                    @if ($available > 0)
                        <form method="post" action="{{ route('student.loans.store') }}">
                            @csrf
                            <input type="hidden" name="book_id" value="{{ Shelfy::id($book) }}">
                            <input type="hidden" name="tanggal_pinjam" value="{{ date('Y-m-d') }}">
                            <input type="hidden" name="tanggal_jatuh_tempo" value="{{ now()->addDays(7)->format('Y-m-d') }}">
                            <button class="button tiny primary" type="submit">Pinjam</button>
                        </form>
                    @else
                        <span class="muted">Habis</span>
                    @endif
                </td>
            </tr>
        @endforeach
        @if ($books->isEmpty())
            <tr><td colspan="6" class="empty">Belum ada buku sesuai filter.</td></tr>
        @endif
        </tbody>
    </table>
</div>
