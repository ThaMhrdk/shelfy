<x-app-layout>
    <section class="page-heading">
        <div>
            <h1>Riwayat & Nota</h1>
            <p>Data pengembalian yang sudah selesai bisa dicetak dan disimpan sebagai PDF.</p>
        </div>
    </section>

    <section class="panel">
        @php $loans = $historyLoans; @endphp
        @include('shelfy.partials.student-loans-table')
    </section>
</x-app-layout>
