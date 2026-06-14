<x-app-layout>
    <section class="page-heading">
        <div>
            <h1>Pengembalian</h1>
            <p>Proses buku yang dikembalikan dan otomatis menambah stok tersedia.</p>
        </div>
    </section>

    @if ($errors->any())
        <div class="alert danger">{{ $errors->first() }}</div>
    @endif

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

    @if ($errors->any() && old('dialog'))
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                document.getElementById(@json(old('dialog')))?.showModal();
            });
        </script>
    @endif
</x-app-layout>
