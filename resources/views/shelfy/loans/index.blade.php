<x-app-layout>
    <section class="page-heading">
        <div>
            <h1>Peminjaman</h1>
            <p>Pantau checkout mahasiswa, catat bukti pengambilan, dan teruskan ke proses pengembalian.</p>
        </div>
    </section>

    <section class="panel">
        @include('shelfy.partials.loan-filters')
        @include('shelfy.partials.loans-table')
    </section>
</x-app-layout>
