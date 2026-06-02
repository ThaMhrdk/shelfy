@php use App\Support\Shelfy; @endphp

<x-app-layout>
    <section class="page-heading">
        <div>
            <h1>Rekapitulasi</h1>
            <p>Contoh aggregation MongoDB: SUM, AVG, COUNT, SORT, dan Greater Than.</p>
        </div>
    </section>

    <section class="metric-grid">
        <article class="metric-card teal">
            <span>SUM Stok Buku</span>
            <strong>{{ Shelfy::moneyless($stats['stok_total']) }}</strong>
            <small>Total semua eksemplar</small>
        </article>
        <article class="metric-card green">
            <span>AVG Stok/Judul</span>
            <strong>{{ number_format((float) $stats['avg_stok'], 1, ',', '.') }}</strong>
            <small>Rata-rata stok per judul</small>
        </article>
        <article class="metric-card amber">
            <span>Greater Than</span>
            <strong>{{ Shelfy::moneyless($stats['gt_five']) }}</strong>
            <small>Judul dengan stok &gt; 5</small>
        </article>
        <article class="metric-card red">
            <span>Terlambat</span>
            <strong>{{ Shelfy::moneyless($stats['terlambat']) }}</strong>
            <small>Peminjaman melewati jatuh tempo</small>
        </article>
    </section>

    @include('shelfy.partials.recap-charts')
</x-app-layout>
