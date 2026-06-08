@php use App\Support\Shelfy; @endphp

<x-app-layout>
    <section class="page-heading">
        <div>
            <h1>Keranjang Peminjaman</h1>
            <p>Periksa buku sebelum checkout peminjaman.</p>
        </div>
        <a class="button" href="{{ route('student.books') }}">Tambah Buku</a>
    </section>

    <section class="panel">
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Buku</th>
                    <th>Tanggal Pinjam</th>
                    <th>Jatuh Tempo</th>
                    <th>Stok Saat Ini</th>
                    <th>Aksi</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($cartItems as $item)
                    @php $book = $books[Shelfy::id($item->book_id)] ?? null; @endphp
                    <tr>
                        <td><strong>{{ $item->judul_buku }}</strong><small>{{ $item->kategori_buku }}</small></td>
                        <td>{{ $item->tanggal_pinjam }}</td>
                        <td>{{ $item->tanggal_jatuh_tempo }}</td>
                        <td>{{ $book ? Shelfy::moneyless($book->stok_tersedia) : 'Buku tidak ditemukan' }}</td>
                        <td>
                            <form method="post" action="{{ route('student.cart.remove', Shelfy::id($item)) }}">
                                @csrf
                                @method('delete')
                                <button class="button tiny" type="submit">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                @if ($cartItems->isEmpty())
                    <tr><td colspan="5" class="empty">Keranjang masih kosong.</td></tr>
                @endif
                </tbody>
            </table>
        </div>

        <form class="checkout-bar" method="post" action="{{ route('student.checkout') }}">
            @csrf
            <span>{{ Shelfy::moneyless($cartItems->count()) }} buku siap checkout</span>
            <button class="button primary" type="submit" @disabled($cartItems->isEmpty())>Checkout Peminjaman</button>
        </form>
    </section>
</x-app-layout>
