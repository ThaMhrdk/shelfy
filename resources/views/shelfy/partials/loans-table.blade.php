@php use App\Support\Shelfy; @endphp

<div class="table-wrap">
    <table>
        <thead>
        <tr>
            <th>Buku</th>
            <th>Anggota</th>
            <th>Tgl Pinjam</th>
            <th>Jatuh Tempo</th>
            <th>Status</th>
            <th>Denda</th>
            <th>Pembayaran</th>
            <th>Aksi</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($loans as $loan)
            @php
                $id = Shelfy::id($loan);
                $fine = $loan->isReturned()
                    ? ['total_denda' => (int) ($loan->total_denda ?? 0)]
                    : Shelfy::lateFee($loan->tanggal_jatuh_tempo);
                $paymentStatus = $loan->isReturned() ? Shelfy::paymentStatus($loan, $fine) : '';
            @endphp
            <tr>
                <td><strong>{{ $loan->judul_buku }}</strong><small>{{ $loan->kategori_buku }}</small></td>
                <td>{{ $loan->nama_anggota }}<small>{{ $loan->nim_anggota }}</small></td>
                <td>{{ $loan->tanggal_pinjam }}</td>
                <td>{{ $loan->tanggal_jatuh_tempo }}</td>
                <td><span class="badge {{ Shelfy::statusClass($loan->status) }}">{{ Shelfy::statusLabel($loan->status) }}</span></td>
                <td>
                    @if ($loan->isReturned() && (int) $fine['total_denda'] > 0)
                        <strong>{{ Shelfy::rupiah($fine['total_denda']) }}</strong>
                    @elseif ($loan->isReturned())
                        <span class="muted">Tidak ada</span>
                    @else
                        <span class="muted">-</span>
                    @endif
                </td>
                <td>
                    @if ($loan->isReturned() && (int) $fine['total_denda'] > 0)
                        <span class="badge {{ Shelfy::statusClass($paymentStatus) }}">{{ Shelfy::statusLabel($paymentStatus) }}</span>
                        <small>{{ Shelfy::paymentMethodLabel($loan->payment_method ?? null) }}</small>
                    @elseif ($loan->isReturned())
                        <span class="badge success">Lunas</span>
                    @else
                        <span class="muted">-</span>
                    @endif
                </td>
                <td class="actions">
                    @if (($canManageLoans ?? false) && $loan->isWaitingPickup())
                        <form class="pickup-form" method="post" action="{{ route('loans.pickup', $id) }}">
                            @csrf
                            <input name="bukti_pengambilan" placeholder="Bukti diambil">
                            <button class="button tiny primary" type="submit">Buku Diambil</button>
                        </form>
                    @elseif (($canManageLoans ?? false) && ! $loan->isReturned())
                        <a class="button tiny" href="{{ route('returns.index') }}">Proses Pengembalian</a>
                    @else
                        @if ($loan->isReturned())
                            <a class="button tiny" href="{{ route('returns.receipt', $id) }}">Nota</a>
                        @else
                            <span class="muted">Pantau</span>
                        @endif
                    @endif
                </td>
            </tr>
        @endforeach
        @if ($loans->isEmpty())
            <tr><td colspan="8" class="empty">Belum ada data peminjaman.</td></tr>
        @endif
        </tbody>
    </table>
</div>
