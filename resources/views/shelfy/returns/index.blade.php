<x-app-layout>
    <section class="page-heading">
        <div>
            <h1>Pengembalian</h1>
            <p>Proses buku yang dikembalikan dan otomatis menambah stok tersedia.</p>
        </div>
    </section>

    <section class="panel">
        <div class="panel-header">
            <div>
                <h2>Proses Pengembalian</h2>
                <p>Buku yang masih dipinjam atau terlambat.</p>
            </div>
        </div>
        @include('shelfy.partials.returns-table')
    </section>

    <section class="panel">
        <div class="panel-header">
            <div>
                <h2>Riwayat Pengembalian</h2>
                <p>Data selesai dan nota formalitas PDF.</p>
            </div>
        </div>
        @php $loans = $returnedLoans; @endphp
        @include('shelfy.partials.student-loans-table')
    </section>
</x-app-layout>
