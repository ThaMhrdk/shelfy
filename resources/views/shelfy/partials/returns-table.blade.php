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
            <th>Pengembalian</th>
            <th>Aksi</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($returnLoans as $loan)
            @php
                $fine = Shelfy::lateFee($loan->tanggal_jatuh_tempo);
                $id = Shelfy::id($loan);
                $extensionCount = count((array) ($loan->riwayat_perpanjangan ?? []));
                $canExtend = ($loan->status ?? '') === 'dipinjam'
                    && (string) ($loan->tanggal_jatuh_tempo ?? '') >= date('Y-m-d');
                try {
                    $currentDueDate = new DateTimeImmutable($loan->tanggal_jatuh_tempo ?: date('Y-m-d'));
                    $minimumDueDate = new DateTimeImmutable(max($currentDueDate->format('Y-m-d'), date('Y-m-d')));
                    $minimumDueDate = $minimumDueDate->modify('+1 day')->format('Y-m-d');
                    $nextDueDate = $currentDueDate->modify('+7 days')->format('Y-m-d');
                } catch (Throwable) {
                    $minimumDueDate = date('Y-m-d', strtotime('+1 day'));
                    $nextDueDate = date('Y-m-d', strtotime('+7 days'));
                }
            @endphp
            <tr>
                <td><strong>{{ $loan->judul_buku }}</strong><small>{{ $loan->kategori_buku }}</small></td>
                <td>{{ $loan->nama_anggota }}<small>{{ $loan->nim_anggota }}</small></td>
                <td>
                    {{ $loan->tanggal_jatuh_tempo }}
                    @if ($extensionCount > 0)
                        <small>Diperpanjang {{ Shelfy::moneyless($extensionCount) }} kali</small>
                    @endif
                </td>
                <td><span class="badge {{ Shelfy::statusClass($loan->status) }}">{{ Shelfy::statusLabel($loan->status) }}</span></td>
                <td>
                    @if ($fine['hari_terlambat'] > 0)
                        <strong>{{ Shelfy::rupiah($fine['total_denda']) }}</strong>
                        <small>{{ Shelfy::moneyless($fine['hari_terlambat']) }} hari terlambat</small>
                    @else
                        <span class="muted">Tidak ada</span>
                    @endif
                </td>
                <td>
                    <form class="return-date-form" id="return-{{ $id }}" method="post" action="{{ route('returns.store') }}">
                        @csrf
                        <input type="hidden" name="from" value="returns">
                        <input type="hidden" name="id" value="{{ $id }}">
                        <input
                            type="date"
                            name="tanggal_kembali"
                            value="{{ old('id') === $id ? old('tanggal_kembali') : '' }}"
                            min="{{ $loan->tanggal_pinjam }}"
                            max="{{ date('Y-m-d') }}"
                            aria-label="Tanggal kembali {{ $loan->judul_buku }}"
                            required
                        >
                    </form>
                </td>
                <td class="actions">
                    <button class="button tiny primary" type="submit" form="return-{{ $id }}">Kembalikan</button>
                    @if ($canExtend)
                        <button class="button tiny" type="button" onclick="document.getElementById('extend-{{ $id }}').showModal()">
                            Perpanjang
                        </button>

                        <dialog class="app-dialog loan-dialog extension-dialog" id="extend-{{ $id }}">
                            <button class="dialog-close" type="button" onclick="this.closest('dialog').close()" aria-label="Tutup">x</button>
                            <form class="stack-form" method="post" action="{{ route('returns.extend', $id) }}">
                                @csrf
                                <input type="hidden" name="dialog" value="extend-{{ $id }}">
                                <h2>Perpanjang Peminjaman</h2>
                                <p class="muted">{{ $loan->judul_buku }} dipinjam oleh {{ $loan->nama_anggota }}.</p>
                                <div class="extension-current">
                                    <span>Jatuh tempo saat ini</span>
                                    <strong>{{ $loan->tanggal_jatuh_tempo }}</strong>
                                </div>
                                <label>Tanggal Jatuh Tempo Baru
                                    <input
                                        type="date"
                                        name="tanggal_jatuh_tempo_baru"
                                        value="{{ old('dialog') === 'extend-'.$id ? old('tanggal_jatuh_tempo_baru', $nextDueDate) : $nextDueDate }}"
                                        min="{{ $minimumDueDate }}"
                                        required
                                    >
                                    @if (old('dialog') === 'extend-'.$id)
                                        @error('tanggal_jatuh_tempo_baru')
                                            <span class="form-error">{{ $message }}</span>
                                        @enderror
                                    @endif
                                </label>
                                <label>Alasan Perpanjangan
                                    <textarea name="catatan_perpanjangan" rows="3" placeholder="Contoh: masih digunakan untuk penyusunan tugas" required>{{ old('dialog') === 'extend-'.$id ? old('catatan_perpanjangan') : '' }}</textarea>
                                    @if (old('dialog') === 'extend-'.$id)
                                        @error('catatan_perpanjangan')
                                            <span class="form-error">{{ $message }}</span>
                                        @enderror
                                    @endif
                                </label>
                                <div class="form-actions">
                                    <button class="button" type="button" onclick="this.closest('dialog').close()">Batal</button>
                                    <button class="button primary" type="submit">Simpan Perpanjangan</button>
                                </div>
                            </form>
                        </dialog>
                    @else
                        <span class="muted">Tidak tersedia</span>
                    @endif
                </td>
            </tr>
        @endforeach
        @if ($returnLoans->isEmpty())
            <tr><td colspan="7" class="empty">Tidak ada peminjaman aktif. Semua aman.</td></tr>
        @endif
        </tbody>
    </table>
</div>
