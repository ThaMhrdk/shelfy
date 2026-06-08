@php use App\Support\Shelfy; @endphp

<x-app-layout>
    <section class="page-heading">
        <div>
            <h1>Detail Buku</h1>
            <p>Informasi buku, stok, dan histori peminjaman.</p>
        </div>
        <a class="button" href="{{ route('books.index') }}">Kembali</a>
    </section>

    <section class="detail-layout">
        <article class="panel detail-hero">
            <div class="book-cover large">
                @if ($book->cover_path)
                    <img src="{{ asset($book->cover_path) }}" alt="Cover {{ $book->judul }}">
                @else
                    <span>{{ strtoupper(substr($book->judul, 0, 1)) }}</span>
                @endif
            </div>
            <div>
                <span class="badge {{ Shelfy::statusClass($book->status()) }}">{{ Shelfy::statusLabel($book->status()) }}</span>
                <h2>{{ $book->judul }}</h2>
                <p>{{ $book->deskripsi ?: 'Deskripsi buku belum diisi.' }}</p>
            </div>
        </article>

        <aside class="panel">
            <h2>Ringkasan Buku</h2>
            <dl class="meta-list">
                <div><dt>Penulis</dt><dd>{{ $book->penulis }}</dd></div>
                <div><dt>Kategori</dt><dd>{{ $book->kategori }}</dd></div>
                <div><dt>Penerbit</dt><dd>{{ $book->penerbit ?: '-' }}</dd></div>
                <div><dt>Tahun</dt><dd>{{ $book->tahun ?: '-' }}</dd></div>
                <div><dt>ISBN</dt><dd>{{ $book->isbn ?: '-' }}</dd></div>
                <div><dt>Stok</dt><dd>{{ Shelfy::moneyless($book->stok_tersedia) }} / {{ Shelfy::moneyless($book->stok_total) }}</dd></div>
                <div><dt>Sedang Diproses/Dipinjam</dt><dd>{{ Shelfy::moneyless($activeLoans) }}</dd></div>
                <div><dt>Selesai</dt><dd>{{ Shelfy::moneyless($returnedLoans) }}</dd></div>
            </dl>
        </aside>
    </section>

    <section class="panel">
        <div class="panel-header">
            <div>
                <h2>Histori Peminjaman Buku</h2>
                <p>Snapshot transaksi yang memakai buku ini.</p>
            </div>
        </div>
        @php $loans = $loans; @endphp
        @include('shelfy.partials.loans-table')
    </section>
</x-app-layout>
