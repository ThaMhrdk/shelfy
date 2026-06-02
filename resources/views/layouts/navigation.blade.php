@php
    use App\Support\Shelfy;

    $user = Auth::user();
    $isAdmin = $user?->isAdmin();
    $navTitle = $isAdmin ? 'Bagian Admin' : 'Bagian Mahasiswa';
    $navItems = $isAdmin ? [
        ['label' => 'Dashboard', 'route' => 'dashboard', 'active' => request()->routeIs('dashboard')],
        ['label' => 'Buku', 'route' => 'books.index', 'active' => request()->routeIs('books.*')],
        ['label' => 'Anggota', 'route' => 'members.index', 'active' => request()->routeIs('members.*')],
        ['label' => 'Peminjaman', 'route' => 'loans.index', 'active' => request()->routeIs('loans.*')],
        ['label' => 'Pengembalian', 'route' => 'returns.index', 'active' => request()->routeIs('returns.*')],
        ['label' => 'Rekap', 'route' => 'recap.index', 'active' => request()->routeIs('recap.*')],
        ['label' => 'Profil', 'route' => 'profile.edit', 'active' => request()->routeIs('profile.*')],
    ] : [
        ['label' => 'Beranda', 'route' => 'student.dashboard', 'active' => request()->routeIs('student.dashboard')],
        ['label' => 'Katalog Buku', 'route' => 'student.books', 'active' => request()->routeIs('student.books')],
        ['label' => 'Peminjaman Saya', 'route' => 'student.loans', 'active' => request()->routeIs('student.loans')],
        ['label' => 'Riwayat & Nota', 'route' => 'student.history', 'active' => request()->routeIs('student.history')],
        ['label' => 'Profil', 'route' => 'profile.edit', 'active' => request()->routeIs('profile.*')],
    ];
@endphp

<aside class="sidebar">
    <a class="brand" href="{{ route(Shelfy::homeRouteName($user)) }}">
        <span class="brand-icon" aria-hidden="true">
            <x-application-logo />
        </span>
        <span>
            <strong>SHELFY</strong>
            <small>Perpustakaan Digital</small>
        </span>
    </a>

    <div class="user-card">
        <span class="avatar">{{ Shelfy::initials($user) }}</span>
        <span>
            <strong>{{ $user?->displayName() ?? 'Pengguna' }}</strong>
            <small>{{ Shelfy::statusLabel($user?->role ?? 'mahasiswa') }}</small>
        </span>
    </div>

    <nav class="nav" aria-label="{{ $navTitle }}">
        <small class="nav-title">{{ $navTitle }}</small>
        @foreach ($navItems as $item)
            <a class="{{ $item['active'] ? 'is-active' : '' }}" href="{{ route($item['route']) }}">
                {{ $item['label'] }}
            </a>
        @endforeach
    </nav>

    <div class="sidebar-note">
        <strong>NoSQL</strong>
        <span>MongoDB database {{ $shelfyMongo['database'] ?? 'shelfy_db' }}</span>
    </div>
</aside>
