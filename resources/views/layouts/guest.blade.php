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
    <body class="auth-body">
        <main class="auth-screen">
            <a class="brand auth-brand" href="{{ route('login') }}">
                <span class="brand-icon" aria-hidden="true">
                    <x-application-logo />
                </span>
                <span>
                    <strong>SHELFY</strong>
                    <small>Perpustakaan Digital</small>
                </span>
            </a>

            @if (! ($shelfyMongo['connected'] ?? false))
                <section class="setup-banner auth-setup">
                    <div>
                        <h2>MongoDB belum tersambung</h2>
                        <p>Login/register butuh MongoDB lokal aktif di <code>mongodb://127.0.0.1:27017</code>.</p>
                        <p class="error-detail">{{ $shelfyMongo['error'] ?? '' }}</p>
                    </div>
                </section>
            @endif

            {{ $slot }}
        </main>
    </body>
</html>
