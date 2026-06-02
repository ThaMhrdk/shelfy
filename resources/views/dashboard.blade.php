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
        <article class="metric-card teal">
            <span>Total Judul</span>
            <strong>{{ Shelfy::moneyless($stats['judul']) }}</strong>
            <small>{{ Shelfy::moneyless($stats['stok_total']) }} total eksemplar</small>
        </article>
        <article class="metric-card green">
            <span>Buku Tersedia</span>
            <strong>{{ Shelfy::moneyless($stats['stok_tersedia']) }}</strong>
            <small>SUM stok tersedia</small>
        </article>
        <article class="metric-card amber">
            <span>Sedang Dipinjam</span>
            <strong>{{ Shelfy::moneyless($stats['dipinjam']) }}</strong>
            <small>Peminjaman aktif</small>
        </article>
        <article class="metric-card red">
            <span>Telat Kembali</span>
            <strong>{{ Shelfy::moneyless($stats['terlambat']) }}</strong>
            <small>Perlu ditindaklanjuti</small>
        </article>
    </section>

    <section class="dashboard-grid">
        <div class="panel wide">
            <div class="panel-header">
                <div>
                    <h2>Daftar Buku</h2>
                    <p>Filter dan CRUD data buku.</p>
                </div>
                <a class="button small" href="{{ route('books.index') }}">Kelola Lengkap</a>
            </div>
            @include('shelfy.partials.books-table')
        </div>
        <aside class="panel sticky-form" id="book-form">
            @include('shelfy.partials.book-form')
        </aside>
    </section>

    @include('shelfy.partials.recap-charts')
</x-app-layout>
