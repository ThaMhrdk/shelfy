<x-app-layout>
    <section class="page-heading">
        <div>
            <h1>Data Buku</h1>
            <p>CRUD buku, pencarian, filter kategori, dan filter stok.</p>
        </div>
    </section>

    <section class="content-grid">
        <div class="panel wide">
            @include('shelfy.partials.book-filters')
            @include('shelfy.partials.books-table')
        </div>
        <aside class="panel sticky-form" id="book-form">
            @include('shelfy.partials.book-form')
        </aside>
    </section>
</x-app-layout>
