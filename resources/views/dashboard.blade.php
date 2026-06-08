@php use App\Support\Shelfy; @endphp

<x-app-layout>
    <section class="page-heading">
        <div>
            <h1>Dashboard</h1>
            <p>Ringkasan aktivitas perpustakaan dan akses cepat kelola buku.</p>
        </div>
        <form method="POST" action="{{ route('shelfy.seed') }}">
            @csrf
            <button class="button primary" type="submit">Isi Data Contoh</button>
        </form>
    </section>

    <section class="metric-grid">
        <a class="metric-card teal" href="{{ route('books.index') }}">
            <span>Total Judul</span>
            <strong>{{ Shelfy::moneyless($stats['judul']) }}</strong>
            <small>{{ Shelfy::moneyless($stats['stok_total']) }} total eksemplar</small>
        </a>
        <a class="metric-card green" href="{{ route('books.index', ['status' => 'tersedia']) }}">
            <span>Buku Tersedia</span>
            <strong>{{ Shelfy::moneyless($stats['stok_tersedia']) }}</strong>
            <small>SUM stok tersedia</small>
        </a>
        <a class="metric-card amber" href="{{ route('loans.index', ['status' => 'dipinjam']) }}">
            <span>Sedang Dipinjam</span>
            <strong>{{ Shelfy::moneyless($stats['dipinjam']) }}</strong>
            <small>Peminjaman aktif</small>
        </a>
        <a class="metric-card red" href="{{ route('loans.index', ['status' => 'terlambat']) }}">
            <span>Telat Kembali</span>
            <strong>{{ Shelfy::moneyless($stats['terlambat']) }}</strong>
            <small>Perlu ditindaklanjuti</small>
        </a>
    </section>

    <section class="panel">
        <div class="panel-header">
            <div>
                <h2>List Buku per Kategori</h2>
                <p>Total judul, stok tersedia, sedang dipinjam, dan telat kembali.</p>
            </div>
        </div>
        <div class="category-table">
            @foreach ($categoryStats as $row)
                <div>
                    <strong>{{ $row->kategori }}</strong>
                    <span>{{ Shelfy::moneyless($row->judul) }} judul</span>
                    <span>{{ Shelfy::moneyless($row->tersedia) }} tersedia</span>
                    <span>{{ Shelfy::moneyless($row->dipinjam) }} dipinjam</span>
                    <span>{{ Shelfy::moneyless($row->terlambat) }} telat</span>
                </div>
            @endforeach
            @if ($categoryStats->isEmpty())
                <p class="muted">Belum ada data kategori.</p>
            @endif
        </div>
    </section>

    <section class="dashboard-grid">
        <div class="panel wide">
            <div class="panel-header">
                <div>
                    <h2>Daftar Buku Terbaru</h2>
                    <p>Tambah dan edit buku dilakukan dari halaman Buku.</p>
                </div>
                <a class="button small" href="{{ route('books.index') }}">Kelola Lengkap</a>
            </div>
            @include('shelfy.partials.books-table', ['readOnlyActions' => true])
        </div>
        <aside class="panel">
            <h2>Info Cepat Peminjaman</h2>
            <div class="compact-list">
                <a href="{{ route('loans.index', ['status' => 'menunggu_diambil']) }}">
                    <strong>Menunggu Diambil</strong>
                    <span>{{ Shelfy::moneyless($borrowedLoans->where('status', 'menunggu_diambil')->count()) }} buku butuh bukti pengambilan</span>
                </a>
                <a href="{{ route('loans.index', ['status' => 'dipinjam']) }}">
                    <strong>Sedang Dipinjam</strong>
                    <span>{{ Shelfy::moneyless($borrowedLoans->where('status', 'dipinjam')->count()) }} transaksi aktif</span>
                </a>
                <a href="{{ route('loans.index', ['status' => 'terlambat']) }}">
                    <strong>Telat Kembali</strong>
                    <span>{{ Shelfy::moneyless($overdueLoans->count()) }} transaksi perlu diproses</span>
                </a>
            </div>
        </aside>
    </section>

    @include('shelfy.partials.recap-charts')
</x-app-layout>
