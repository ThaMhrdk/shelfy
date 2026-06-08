@php use App\Support\Shelfy; @endphp

<div class="catalog-grid">
    @foreach ($books as $book)
        @php
            $available = (int) ($book->stok_tersedia ?? 0);
            $status = $available > 0 ? 'tersedia' : 'habis';
            $bookId = Shelfy::id($book);
            $inCart = in_array($bookId, $cartBookIds ?? [], true);
        @endphp
        <article class="catalog-card">
            <a class="catalog-cover" href="{{ route('student.books.show', $bookId) }}">
                @if ($book->cover_path)
                    <img src="{{ asset($book->cover_path) }}" alt="Cover {{ $book->judul }}">
                @else
                    <span>{{ strtoupper(substr($book->judul, 0, 1)) }}</span>
                @endif
            </a>
            <div class="catalog-info">
                <span class="catalog-category">{{ $book->kategori }}</span>
                <h2><a href="{{ route('student.books.show', $bookId) }}">{{ $book->judul }}</a></h2>
                <p>{{ $book->penulis }}</p>
                <div class="catalog-meta">
                    <span class="badge {{ Shelfy::statusClass($status) }}">{{ Shelfy::statusLabel($status) }}</span>
                    <small>Stok {{ Shelfy::moneyless($available) }} / {{ Shelfy::moneyless($book->stok_total) }}</small>
                </div>
            </div>
            <div class="catalog-actions">
                <a class="button tiny" href="{{ route('student.books.show', $bookId) }}">Detail</a>
                @if ($available > 0)
                    <button class="button tiny primary" type="button" onclick="document.getElementById('cart-{{ $bookId }}').showModal()">
                        {{ $inCart ? 'Ubah Keranjang' : 'Masuk Keranjang' }}
                    </button>
                    <dialog class="app-dialog loan-dialog" id="cart-{{ $bookId }}">
                        <button class="dialog-close" type="button" onclick="this.closest('dialog').close()" aria-label="Tutup">x</button>
                        <form class="stack-form" method="post" action="{{ route('student.cart.add') }}">
                            @csrf
                            <input type="hidden" name="book_id" value="{{ $bookId }}">
                            <h2>{{ $book->judul }}</h2>
                            <p class="muted">Atur tanggal peminjaman lalu masukkan ke keranjang.</p>
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
                                <button class="button" type="button" onclick="this.closest('dialog').close()">Batal</button>
                                <button class="button primary" type="submit">Simpan ke Keranjang</button>
                            </div>
                        </form>
                    </dialog>
                @else
                    <span class="muted">Stok habis</span>
                @endif
            </div>
        </article>
    @endforeach
    @if ($books->isEmpty())
        <p class="empty catalog-empty">Belum ada buku sesuai filter.</p>
    @endif
</div>
