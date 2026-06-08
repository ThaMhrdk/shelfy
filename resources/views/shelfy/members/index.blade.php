<x-app-layout>
    <section class="page-heading">
        <div>
            <h1>Direktori Anggota</h1>
            <p>Data anggota berasal dari register dan profil mahasiswa. Petugas hanya memantau status layanan.</p>
        </div>
    </section>

    <section class="panel">
        <div class="panel-header">
            <div>
                <h2>Anggota Terdaftar</h2>
                <p>Kontak pribadi seperti alamat dan nomor HP dikelola sendiri oleh mahasiswa di Profil.</p>
            </div>
        </div>
        @include('shelfy.partials.member-filters')
        @include('shelfy.partials.members-table')
    </section>
</x-app-layout>
