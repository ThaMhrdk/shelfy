@php use App\Support\Shelfy; @endphp

<div class="table-wrap">
    <table>
        <thead>
        <tr>
            <th>Buku</th>
            <th>Tgl Pinjam</th>
            <th>Jatuh Tempo</th>
            <th>Tgl Kembali</th>
            <th>Status</th>
            <th>Pembayaran</th>
            <th>Nota</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($loans as $loan)
            @php
                $isReturned = $loan->isReturned();
                $fine = $isReturned
                    ? ['total_denda' => (int) ($loan->total_denda ?? 0)]
                    : ['total_denda' => 0];
                $paymentStatus = $isReturned ? Shelfy::paymentStatus($loan, $fine) : '';
            @endphp
            <tr>
                <td><strong>{{ $loan->judul_buku }}</strong><small>{{ $loan->kategori_buku }}</small></td>
                <td>{{ $loan->tanggal_pinjam }}</td>
                <td>{{ $loan->tanggal_jatuh_tempo }}</td>
                <td>{{ $loan->tanggal_kembali ?: '-' }}</td>
                <td><span class="badge {{ Shelfy::statusClass($loan->status) }}">{{ Shelfy::statusLabel($loan->status) }}</span></td>
                <td>
                    @if ($isReturned && (int) $fine['total_denda'] > 0)
                        <span class="badge {{ Shelfy::statusClass($paymentStatus) }}">{{ Shelfy::statusLabel($paymentStatus) }}</span>
                        <small>{{ Shelfy::rupiah($fine['total_denda']) }}{{ ($loan->payment_method ?? '') ? ' - ' . Shelfy::paymentMethodLabel($loan->payment_method) : '' }}</small>
                    @elseif ($isReturned)
                        <span class="badge success">Bebas Denda</span>
                    @else
                        <span class="muted">-</span>
                    @endif
                </td>
                <td>
                    @if ($isReturned)
                        <a class="button tiny {{ (int) $fine['total_denda'] > 0 && $paymentStatus === 'belum_bayar' ? 'primary' : '' }}" href="{{ route('returns.receipt', Shelfy::id($loan)) }}">
                            {{ (int) $fine['total_denda'] > 0 && $paymentStatus === 'belum_bayar' ? 'Bayar' : 'Cetak PDF' }}
                        </a>
                    @else
                        <span class="muted">Belum selesai</span>
                    @endif
                </td>
            </tr>
        @endforeach
        @if ($loans->isEmpty())
            <tr><td colspan="7" class="empty">Belum ada data peminjaman untuk akun ini.</td></tr>
        @endif
        </tbody>
    </table>
</div>
