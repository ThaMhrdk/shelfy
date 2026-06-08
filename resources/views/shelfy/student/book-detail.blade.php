@php use App\Support\Shelfy; @endphp

<x-app-layout>
    <section class="page-heading">
        <div>
            <h1>Detail Buku</h1>
            <p>Lihat informasi buku sebelum dimasukkan ke keranjang.</p>
        </div>
        <a class="button" href="{{ route('student.books') }}">Kembali</a>
    </section>

    <section class="detail-layout">
        <article class="panel detail-hero">
            <div class="book-cover large">
                @if ($book->cover_path)
                    <img src="{{ Shelfy::fileUrl($book->cover_path) }}" alt="Cover {{ $book->judul }}">
                @else
                    <span>{{ strtoupper(substr($book->judul, 0, 1)) }}</span>
                @endif
            </div>
            <div>
                <span class="badge {{ Shelfy::statusClass($book->status()) }}">{{ Shelfy::statusLabel($book->status()) }}</span>
                <h2>{{ $book->judul }}</h2>
                <p>{{ $book->deskripsi ?: 'Deskripsi buku belum diisi.' }}</p>
            </div>
        </article>

        <aside class="panel">
            <h2>Informasi Buku</h2>
            <dl class="meta-list">
                <div><dt>Penulis</dt><dd>{{ $book->penulis }}</dd></div>
                <div><dt>Kategori</dt><dd>{{ $book->kategori }}</dd></div>
                <div><dt>Penerbit</dt><dd>{{ $book->penerbit ?: '-' }}</dd></div>
                <div><dt>Tahun</dt><dd>{{ $book->tahun ?: '-' }}</dd></div>
                <div><dt>ISBN</dt><dd>{{ $book->isbn ?: '-' }}</dd></div>
                <div><dt>Stok</dt><dd>{{ Shelfy::moneyless($book->stok_tersedia) }} tersedia</dd></div>
            </dl>

            @if ((int) ($book->stok_tersedia ?? 0) > 0)
                <button class="button primary full" type="button" onclick="document.getElementById('cart-detail').showModal()">
                    {{ $alreadyInCart ? 'Ubah Keranjang' : 'Masuk Keranjang' }}
                </button>
                <dialog class="loan-dialog" id="cart-detail">
                    <form class="stack-form" method="post" action="{{ route('student.cart.add') }}">
                        @csrf
                        <input type="hidden" name="book_id" value="{{ Shelfy::id($book) }}">
                        <h2>Tambah ke Keranjang</h2>
                        <label>Tanggal Pinjam
                            <input type="date" name="tanggal_pinjam" value="{{ date('Y-m-d') }}" required>
                        </label>
                        <label>Jatuh Tempo
                            <input type="date" name="tanggal_jatuh_tempo" value="{{ now()->addDays(7)->format('Y-m-d') }}" required>
                        </label>
                        <label>Catatan
                            <textarea name="catatan" rows="3" placeholder="Opsional"></textarea>
                        </label>
                        <div class="form-actions">
                            <button class="button" type="button" onclick="document.getElementById('cart-detail').close()">Batal</button>
                            <button class="button primary" type="submit">Simpan ke Keranjang</button>
                        </div>
                    </form>
                </dialog>
            @endif
        </aside>
    </section>
</x-app-layout>
