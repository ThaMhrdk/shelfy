@php
    use App\Support\Shelfy;

    $loan = $receipt;
    $fine = [
        'hari_terlambat' => (int) ($loan->hari_terlambat ?? 0),
        'denda_per_hari' => (int) ($loan->denda_per_hari ?? Shelfy::FINE_PER_DAY),
        'total_denda' => (int) ($loan->total_denda ?? 0),
    ];
    $hasFine = (int) $fine['total_denda'] > 0;
    $paymentStatus = Shelfy::paymentStatus($loan, $fine);
    $paymentMethod = Shelfy::paymentMethodLabel($loan->payment_method ?? null);
    $currentUser = auth()->user();
    $canStudentPay = $currentUser?->isStudent() && $hasFine && $paymentStatus === 'belum_bayar';
    $canStaffConfirm = $currentUser?->isLibrarian() && $hasFine && $paymentStatus === 'menunggu_konfirmasi';
@endphp

<x-app-layout>
    <section class="page-heading no-print">
        <div>
            <h1>Nota Pengembalian</h1>
            <p>Halaman ini bisa dicetak lalu pilih Save as PDF atau Microsoft Print to PDF.</p>
        </div>
        <button class="button primary" type="button" onclick="window.print()">Cetak / Simpan PDF</button>
    </section>

    <section class="receipt-paper">
        <header>
            <div>
                <h1>SHELFY</h1>
                <p>Nota Pengembalian Buku Perpustakaan Digital</p>
            </div>
            <strong>{{ $receiptNumber }}</strong>
        </header>

        <dl class="receipt-details">
            <div><dt>Nama Anggota</dt><dd>{{ $loan->nama_anggota ?? '-' }}</dd></div>
            <div><dt>NIM</dt><dd>{{ $loan->nim_anggota ?? '-' }}</dd></div>
            <div><dt>Judul Buku</dt><dd>{{ $loan->judul_buku ?? '-' }}</dd></div>
            <div><dt>Kategori</dt><dd>{{ $loan->kategori_buku ?? '-' }}</dd></div>
            <div><dt>Tanggal Pinjam</dt><dd>{{ $loan->tanggal_pinjam ?? '-' }}</dd></div>
            <div><dt>Jatuh Tempo</dt><dd>{{ $loan->tanggal_jatuh_tempo ?? '-' }}</dd></div>
            <div><dt>Tanggal Kembali</dt><dd>{{ $loan->tanggal_kembali ?? '-' }}</dd></div>
            <div><dt>Status</dt><dd>{{ Shelfy::statusLabel($loan->status ?? '-') }}</dd></div>
            <div><dt>Hari Terlambat</dt><dd>{{ Shelfy::moneyless($fine['hari_terlambat']) }} hari</dd></div>
            <div><dt>Denda Per Hari</dt><dd>{{ Shelfy::rupiah($fine['denda_per_hari']) }}</dd></div>
            <div><dt>Total Denda</dt><dd>{{ Shelfy::rupiah($fine['total_denda']) }}</dd></div>
            <div>
                <dt>Status Pembayaran</dt>
                <dd><span class="badge {{ Shelfy::statusClass($paymentStatus) }}">{{ Shelfy::statusLabel($paymentStatus) }}</span></dd>
            </div>
            @if ($hasFine)
                <div><dt>Metode Pembayaran</dt><dd>{{ $paymentMethod }}</dd></div>
                <div><dt>Kode Pembayaran</dt><dd>{{ $loan->payment_reference ?? '-' }}</dd></div>
            @endif
        </dl>

        <div class="receipt-note {{ $hasFine ? 'fine-due' : '' }}">
            <strong>{{ $hasFine ? 'Catatan denda:' : 'Catatan:' }}</strong>
            <span>
                @if ($hasFine && $paymentStatus === 'belum_bayar')
                    Denda {{ Shelfy::rupiah($fine['total_denda']) }} belum dibayar.
                @elseif ($hasFine && $paymentStatus === 'menunggu_konfirmasi')
                    Pembayaran via {{ $paymentMethod }} sudah dicatat dan menunggu konfirmasi pustakawan.
                @elseif ($hasFine)
                    Denda {{ Shelfy::rupiah($fine['total_denda']) }} sudah lunas via {{ $paymentMethod }}.
                @else
                    Tidak ada denda. Buku diterima kembali tepat waktu.
                @endif
            </span>
        </div>

        @if ($hasFine)
            <div class="payment-panel no-print">
                <h2>Pembayaran Denda</h2>
                @if ($canStudentPay)
                    <p>Pilih metode pembayaran. Ini simulasi untuk kebutuhan TUBES, jadi tidak terhubung ke bank asli.</p>
                    <form class="payment-form" method="post" action="{{ route('returns.receipt.pay', Shelfy::id($loan)) }}">
                        @csrf
                        <div class="payment-options">
                            @foreach (Shelfy::paymentMethodOptions() as $value => $label)
                                <label class="payment-option">
                                    <input type="radio" name="payment_method" value="{{ $value }}" @checked($value === 'transfer')>
                                    <span>
                                        <strong>{{ $label }}</strong>
                                        <small>{{ $value === 'transfer' ? 'Simulasi transfer ke rekening perpustakaan.' : 'Simulasi bayar lewat QRIS.' }}</small>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        <button class="button primary full" type="submit">Bayar Denda</button>
                    </form>
                @elseif ($canStaffConfirm)
                    <p>Mahasiswa sudah memilih {{ $paymentMethod }}. Petugas tinggal mengonfirmasi pembayaran ini sebagai lunas.</p>
                    <form method="post" action="{{ route('returns.receipt.confirm', Shelfy::id($loan)) }}">
                        @csrf
                        <button class="button primary" type="submit">Konfirmasi Lunas</button>
                    </form>
                @elseif ($paymentStatus === 'belum_bayar')
                    <p>Menunggu mahasiswa memilih metode pembayaran.</p>
                @else
                    <p>Status pembayaran saat ini: <strong>{{ Shelfy::statusLabel($paymentStatus) }}</strong>.</p>
                @endif
            </div>
        @endif

        <footer>
            <div>
                <span>Mahasiswa</span>
                <strong>{{ $loan->nama_anggota ?? '-' }}</strong>
            </div>
            <div>
                <span>Petugas</span>
                <strong>{{ $loan->petugas_pengembalian ?? 'Admin SHELFY' }}</strong>
            </div>
        </footer>
    </section>
</x-app-layout>
