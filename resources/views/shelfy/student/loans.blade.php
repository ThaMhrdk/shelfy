<x-app-layout>
    <section class="page-heading">
        <div>
            <h1>Peminjaman Saya</h1>
            <p>Pantau status buku yang diajukan dari katalog dan checkout keranjang.</p>
        </div>
        <a class="button primary" href="{{ route('student.books') }}">Cari Buku</a>
    </section>

    <section class="panel">
        @include('shelfy.partials.loan-filters', ['filterRoute' => route('student.loans')])
        @include('shelfy.partials.student-loans-table')
    </section>
</x-app-layout>
