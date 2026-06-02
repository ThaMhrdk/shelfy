@php use App\Support\Shelfy; @endphp

<section class="chart-grid">
    <article class="panel">
        <h2>5 Buku Paling Sering Dipinjam</h2>
        <div class="bar-list">
            @foreach ($topBooks as $book)
                @php $count = max(1, (int) ($book->dipinjam_count ?? 0)); @endphp
                <div class="bar-row">
                    <span>{{ $book->judul }}</span>
                    <div><i style="width: {{ min(100, $count * 12) }}%"></i></div>
                    <b>{{ Shelfy::moneyless($book->dipinjam_count) }}</b>
                </div>
            @endforeach
            @if ($topBooks->isEmpty())
                <p class="muted">Belum ada data buku.</p>
            @endif
        </div>
    </article>
    <article class="panel">
        <h2>Peminjaman per Kategori</h2>
        <div class="category-list">
            @foreach ($categoryLoans as $row)
                <div>
                    <span>{{ $row->kategori ?: 'Tanpa Kategori' }}</span>
                    <strong>{{ Shelfy::moneyless($row->total) }}</strong>
                </div>
            @endforeach
            @if ($categoryLoans->isEmpty())
                <p class="muted">Belum ada data peminjaman.</p>
            @endif
        </div>
    </article>
</section>
