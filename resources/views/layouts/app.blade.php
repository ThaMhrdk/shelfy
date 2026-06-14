<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <div class="app-shell">
            @include('layouts.navigation')

            <main class="main">
                <header class="topbar">
                    <form class="global-search" method="get" action="{{ url()->current() }}">
                        <input name="q" value="{{ request('q') }}" placeholder="Cari buku, anggota, atau peminjaman...">
                        <button type="submit" title="Cari">
                            <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m16 16 4 4"/></svg>
                        </button>
                    </form>
                    <div class="topbar-actions">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="button small" type="submit">Logout</button>
                        </form>
                    </div>
                </header>

                @foreach (['success', 'danger', 'status'] as $messageType)
                    @if (session($messageType))
                        <div class="alert {{ $messageType === 'status' ? 'success' : $messageType }}">{{ session($messageType) }}</div>
                    @endif
                @endforeach

                @if (! ($shelfyMongo['connected'] ?? false) || isset($mongoError))
                    <section class="setup-banner">
                        <div>
                            <h2>MongoDB belum tersambung</h2>
                            <p>Aplikasi Laravel Breeze sudah siap, tetapi koneksi MongoDB lokal belum tersedia.</p>
                            <p class="error-detail">{{ $mongoError ?? ($shelfyMongo['error'] ?? '') }}</p>
                        </div>
                    </section>
                @endif

                {{ $slot }}
            </main>
        </div>
    </body>
</html>
