<x-app-layout>
    <section class="page-heading">
        <div>
            <h1>Peminjaman Saya</h1>
            <p>Ajukan peminjaman baru dan pantau status buku yang sedang dipinjam.</p>
        </div>
    </section>

    <section class="content-grid">
        <div class="panel wide">
            @include('shelfy.partials.loan-filters', ['filterRoute' => route('student.loans')])
            @include('shelfy.partials.student-loans-table')
        </div>
        <aside class="panel sticky-form">
            @include('shelfy.partials.student-loan-form')
        </aside>
    </section>
</x-app-layout>
