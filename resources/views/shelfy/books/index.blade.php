<x-app-layout>
    <section class="page-heading">
        <div>
            <h1>Data Buku</h1>
            <p>Katalog inventaris buku, pencarian, filter kategori, dan filter stok.</p>
        </div>
        @if ($canManageBooks ?? false)
            <button class="button primary" type="button" onclick="document.getElementById('book-create-dialog').showModal()">Tambah Buku</button>
        @endif
    </section>

    <section class="panel">
        @include('shelfy.partials.book-filters')
        @include('shelfy.partials.books-table')
    </section>

    @if ($canManageBooks ?? false)
        <dialog class="app-dialog book-dialog" id="book-create-dialog">
            <button class="dialog-close" type="button" onclick="this.closest('dialog').close()" aria-label="Tutup">x</button>
            @include('shelfy.partials.book-form', ['editBook' => null])
        </dialog>

        @foreach ($books as $book)
            <dialog class="app-dialog book-dialog" id="book-edit-{{ App\Support\Shelfy::id($book) }}">
                <button class="dialog-close" type="button" onclick="this.closest('dialog').close()" aria-label="Tutup">x</button>
                @include('shelfy.partials.book-form', ['editBook' => $book])
            </dialog>
        @endforeach

        @if ($errors->any() && old('dialog'))
            <script>
                window.addEventListener('DOMContentLoaded', () => {
                    document.getElementById(@json(old('dialog')))?.showModal();
                });
            </script>
        @endif
    @endif
</x-app-layout>
