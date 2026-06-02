@php use App\Support\Shelfy; @endphp

<div class="table-wrap">
    <table>
        <thead>
        <tr>
            <th>Nama</th>
            <th>NIM</th>
            <th>Prodi</th>
            <th>Kontak</th>
            <th>Status</th>
            <th>Aksi</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($members as $member)
            @php $id = Shelfy::id($member); @endphp
            <tr>
                <td><strong>{{ $member->nama }}</strong><small>{{ $member->alamat ?: 'Alamat belum diisi' }}</small></td>
                <td>{{ $member->nim }}</td>
                <td>{{ $member->prodi }}</td>
                <td>{{ $member->email }}<small>{{ $member->no_hp }}</small></td>
                <td><span class="badge {{ Shelfy::statusClass($member->status) }}">{{ Shelfy::statusLabel($member->status) }}</span></td>
                <td class="actions">
                    <a class="icon-button" title="Edit anggota" href="{{ route('members.index', ['edit' => $id]) }}#member-form">
                        <svg viewBox="0 0 24 24"><path d="M4 20h4l11-11a2.8 2.8 0 0 0-4-4L4 16v4Z"/><path d="m13.5 6.5 4 4"/></svg>
                    </a>
                    <form method="post" action="{{ route('members.destroy', $id) }}" onsubmit="return confirm('Hapus anggota ini?')">
                        @csrf
                        @method('delete')
                        <button class="icon-button danger" title="Hapus anggota" type="submit">
                            <svg viewBox="0 0 24 24"><path d="M4 7h16"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M6 7l1 13h10l1-13"/><path d="M9 7V4h6v3"/></svg>
                        </button>
                    </form>
                </td>
            </tr>
        @endforeach
        @if ($members->isEmpty())
            <tr><td colspan="6" class="empty">Belum ada data anggota.</td></tr>
        @endif
        </tbody>
    </table>
</div>
