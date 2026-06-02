<x-app-layout>
    <section class="page-heading">
        <div>
            <h1>Data Anggota</h1>
            <p>CRUD anggota perpustakaan dan filter status.</p>
        </div>
    </section>

    <section class="content-grid">
        <div class="panel wide">
            @include('shelfy.partials.member-filters')
            @include('shelfy.partials.members-table')
        </div>
        <aside class="panel sticky-form" id="member-form">
            @include('shelfy.partials.member-form')
        </aside>
    </section>
</x-app-layout>
