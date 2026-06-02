@php use App\Support\Shelfy; @endphp

<x-app-layout>
    <section class="page-heading">
        <div>
            <h1>Beranda Mahasiswa</h1>
            <p>Pantau peminjaman pribadi dan pilih buku yang masih tersedia.</p>
        </div>
        <a class="button primary" href="{{ route('student.books') }}">Cari Buku</a>
    </section>

    <section class="metric-grid">
        <article class="metric-card teal">
            <span>Peminjaman Aktif</span>
            <strong>{{ Shelfy::moneyless($studentStats['aktif']) }}</strong>
            <small>Buku yang masih dipegang</small>
        </article>
        <article class="metric-card amber">
            <span>Terlambat</span>
            <strong>{{ Shelfy::moneyless($studentStats['terlambat']) }}</strong>
            <small>Perlu segera dikembalikan</small>
        </article>
        <article class="metric-card green">
            <span>Selesai</span>
            <strong>{{ Shelfy::moneyless($studentStats['selesai']) }}</strong>
            <small>Sudah punya nota pengembalian</small>
        </article>
        <article class="metric-card red">
            <span>Role</span>
            <strong>Mahasiswa</strong>
            <small>Akses mandiri katalog dan riwayat</small>
        </article>
    </section>

    <section class="dashboard-grid">
        <div class="panel wide">
            <div class="panel-header">
                <div>
                    <h2>Peminjaman Terbaru</h2>
                    <p>Riwayat singkat akun kamu.</p>
                </div>
                <a class="button small" href="{{ route('student.loans') }}">Lihat Semua</a>
            </div>
            @include('shelfy.partials.student-loans-table')
        </div>
        <aside class="panel sticky-form">
            <h2>Buku Tersedia</h2>
            <div class="compact-list">
                @foreach ($books->take(6) as $book)
                    <a href="{{ route('student.books', ['q' => $book->judul]) }}">
                        <strong>{{ $book->judul }}</strong>
                        <span>{{ $book->kategori }} | stok {{ $book->stok_tersedia }}</span>
                    </a>
                @endforeach
                @if ($books->isEmpty())
                    <p class="muted">Belum ada buku tersedia.</p>
                @endif
            </div>
        </aside>
    </section>
</x-app-layout>
