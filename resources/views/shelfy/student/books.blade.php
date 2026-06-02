<x-app-layout>
    <section class="page-heading">
        <div>
            <h1>Katalog Buku</h1>
            <p>Mahasiswa bisa mencari, memfilter, dan mengajukan peminjaman buku yang tersedia.</p>
        </div>
    </section>

    <section class="panel">
        @include('shelfy.partials.book-filters', ['filterRoute' => route('student.books')])
        @include('shelfy.partials.student-books-table')
    </section>
</x-app-layout>
