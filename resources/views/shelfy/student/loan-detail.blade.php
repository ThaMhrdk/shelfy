@php use App\Support\Shelfy; @endphp

<x-app-layout>
    <section class="page-heading">
        <div>
            <h1>Detail Peminjaman</h1>
            <p>Status peminjaman dan bukti pengambilan buku.</p>
        </div>
        <a class="button" href="{{ route('student.loans') }}">Kembali</a>
    </section>

    <section class="detail-layout">
        <article class="panel">
            <h2>{{ $loan->judul_buku }}</h2>
            <dl class="meta-list">
                <div><dt>Kategori</dt><dd>{{ $loan->kategori_buku ?: '-' }}</dd></div>
                <div><dt>Tanggal Pinjam</dt><dd>{{ $loan->tanggal_pinjam ?: '-' }}</dd></div>
                <div><dt>Jatuh Tempo</dt><dd>{{ $loan->tanggal_jatuh_tempo ?: '-' }}</dd></div>
                <div><dt>Tanggal Kembali</dt><dd>{{ $loan->tanggal_kembali ?: '-' }}</dd></div>
                <div><dt>Status</dt><dd><span class="badge {{ Shelfy::statusClass($loan->status) }}">{{ Shelfy::statusLabel($loan->status) }}</span></dd></div>
                <div><dt>Catatan</dt><dd>{{ $loan->catatan ?: '-' }}</dd></div>
            </dl>
        </article>

        <aside class="panel">
            <h2>Bukti Pengambilan</h2>
            @if ($loan->tanggal_diambil)
                <dl class="meta-list">
                    <div><dt>Tanggal Diambil</dt><dd>{{ $loan->tanggal_diambil }}</dd></div>
                    <div><dt>Petugas</dt><dd>{{ $loan->petugas_pengambilan ?: '-' }}</dd></div>
                    <div><dt>Bukti</dt><dd>{{ $loan->bukti_pengambilan ?: '-' }}</dd></div>
                </dl>
            @else
                <p class="muted">Buku belum dikonfirmasi diambil. Tunjukkan halaman ini ke pustakawan.</p>
            @endif

            @if ($loan->isReturned())
                <a class="button primary full" href="{{ route('returns.receipt', Shelfy::id($loan)) }}">Lihat Nota</a>
            @endif
        </aside>
    </section>
</x-app-layout>
