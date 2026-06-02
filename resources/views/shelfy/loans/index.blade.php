<x-app-layout>
    <section class="page-heading">
        <div>
            <h1>Peminjaman</h1>
            <p>Catat transaksi peminjaman dan pantau statusnya.</p>
        </div>
    </section>

    <section class="content-grid">
        <div class="panel wide">
            @include('shelfy.partials.loan-filters')
            @include('shelfy.partials.loans-table')
        </div>
        <aside class="panel sticky-form">
            @include('shelfy.partials.loan-form')
        </aside>
    </section>
</x-app-layout>
