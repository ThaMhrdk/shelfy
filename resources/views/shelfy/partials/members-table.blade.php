@php use App\Support\Shelfy; @endphp

<div class="table-wrap">
    <table>
        <thead>
        <tr>
            <th>Nama</th>
            <th>NIM</th>
            <th>Prodi</th>
            <th>Email</th>
            <th>Status</th>
            <th>Peminjaman Aktif</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($members as $member)
            @php $id = Shelfy::id($member); @endphp
            <tr>
                <td><strong>{{ $member->nama }}</strong><small>Terdaftar sebagai mahasiswa</small></td>
                <td>{{ $member->nim }}</td>
                <td>{{ $member->prodi }}</td>
                <td>{{ $member->email ?: '-' }}</td>
                <td><span class="badge {{ Shelfy::statusClass($member->status) }}">{{ Shelfy::statusLabel($member->status) }}</span></td>
                <td>{{ Shelfy::moneyless((int) ($activeLoanCounts[$id] ?? 0)) }}</td>
            </tr>
        @endforeach
        @if ($members->isEmpty())
            <tr><td colspan="6" class="empty">Belum ada data anggota.</td></tr>
        @endif
        </tbody>
    </table>
</div>
