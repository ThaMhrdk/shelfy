@php use App\Support\Shelfy; @endphp

<div class="table-wrap">
    <table>
        <thead>
        <tr>
            <th>Buku</th>
            <th>Anggota</th>
            <th>Jatuh Tempo</th>
            <th>Status</th>
            <th>Denda</th>
            <th>Tanggal Kembali</th>
            <th>Aksi</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($returnLoans as $loan)
            @php
                $fine = Shelfy::lateFee($loan->tanggal_jatuh_tempo);
                $id = Shelfy::id($loan);
            @endphp
            <tr>
                <td><strong>{{ $loan->judul_buku }}</strong><small>{{ $loan->kategori_buku }}</small></td>
                <td>{{ $loan->nama_anggota }}<small>{{ $loan->nim_anggota }}</small></td>
                <td>{{ $loan->tanggal_jatuh_tempo }}</td>
                <td><span class="badge {{ Shelfy::statusClass($loan->status) }}">{{ Shelfy::statusLabel($loan->status) }}</span></td>
                <td>
                    @if ($fine['hari_terlambat'] > 0)
                        <strong>{{ Shelfy::rupiah($fine['total_denda']) }}</strong>
                        <small>{{ Shelfy::moneyless($fine['hari_terlambat']) }} hari terlambat</small>
                    @else
                        <span class="muted">Tidak ada</span>
                    @endif
                </td>
                <td colspan="2">
                    <form class="return-form" method="post" action="{{ route('returns.store') }}">
                        @csrf
                        <input type="hidden" name="from" value="returns">
                        <input type="hidden" name="id" value="{{ $id }}">
                        <input type="date" name="tanggal_kembali" value="{{ date('Y-m-d') }}">
                        <button class="button tiny primary" type="submit">Proses</button>
                    </form>
                </td>
            </tr>
        @endforeach
        @if ($returnLoans->isEmpty())
            <tr><td colspan="7" class="empty">Tidak ada peminjaman aktif. Semua aman.</td></tr>
        @endif
        </tbody>
    </table>
</div>
