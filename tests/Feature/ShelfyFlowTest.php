<?php

use App\Models\Book;
use App\Models\CartItem;
use App\Models\Loan;
use App\Models\Member;
use App\Models\User;
use App\Support\Shelfy;

test('admin can open shelfy admin pages and nota route exists', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/dashboard')->assertOk()->assertDontSee('Migrasi Laravel - Progress');
    $this->actingAs($user)->get('/books')->assertOk()->assertSee('Data Buku')->assertSee('Tambah Buku');
    $this->actingAs($user)->get('/members')->assertOk()->assertSee('Direktori Anggota');
    $this->actingAs($user)->get('/loans')->assertOk()->assertSee('Peminjaman')->assertDontSee('Tambah Peminjaman');
    $this->actingAs($user)->get('/returns')->assertOk()->assertSee('Pengembalian');
    $this->actingAs($user)->get('/recap')->assertOk()->assertSee('Rekapitulasi');
    $routeNames = collect(app('router')->getRoutes())->map(fn ($route) => $route->getName())->filter();
    expect($routeNames->contains('books.create'))->toBeFalse();
    expect($routeNames->contains('books.edit'))->toBeFalse();

    expect($routeNames->contains('returns.receipt'))->toBeTrue();
});

test('pustakawan can add and update book stock from catalog modal flow', function () {
    $pustakawan = User::factory()->create(['role' => 'pustakawan']);
    Book::query()->create([
        'judul' => 'Buku Awal Modal',
        'penulis' => 'SHELFY',
        'kategori' => 'Referensi',
        'stok_total' => 1,
        'stok_tersedia' => 1,
        'dipinjam_count' => 0,
    ]);

    $this->actingAs($pustakawan)
        ->get('/books')
        ->assertOk()
        ->assertSee('Tambah Buku')
        ->assertSee('Edit buku');

    $this->actingAs($pustakawan)->post(route('books.store'), [
        'judul' => 'Inventaris Pustakawan',
        'penulis' => 'SHELFY',
        'kategori' => 'Referensi',
        'penerbit' => 'SHELFY',
        'tahun' => 2026,
        'stok_total' => 2,
        'isbn' => '9780000000999',
        'deskripsi' => 'Buku yang ditambah pustakawan.',
        'dialog' => 'book-create-dialog',
    ])->assertRedirect(route('books.index'))->assertSessionHas('success');

    $book = Book::query()->where('judul', 'Inventaris Pustakawan')->first();
    expect($book)->not->toBeNull();
    expect($book->stok_total)->toBe(2);
    expect($book->stok_tersedia)->toBe(2);

    $this->actingAs($pustakawan)->post(route('books.store'), [
        'id' => Shelfy::id($book),
        'judul' => 'Inventaris Pustakawan',
        'penulis' => 'SHELFY',
        'kategori' => 'Referensi',
        'penerbit' => 'SHELFY',
        'tahun' => 2026,
        'stok_total' => 5,
        'isbn' => '9780000000999',
        'deskripsi' => 'Stok ditambah pustakawan.',
        'dialog' => 'book-edit-' . Shelfy::id($book),
    ])->assertRedirect(route('books.index'))->assertSessionHas('success');

    expect($book->refresh()->stok_total)->toBe(5);
    expect($book->stok_tersedia)->toBe(5);
});

test('book member loan return and nota flow works', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/books', [
        'judul' => 'Tes MongoDB Compass',
        'penulis' => 'Admin SHELFY',
        'kategori' => 'Teknologi Informasi',
        'penerbit' => 'SHELFY',
        'tahun' => 2026,
        'stok_total' => 2,
        'isbn' => '9780000000001',
        'deskripsi' => 'Data test',
    ])->assertRedirect(route('books.index'));

    $book = Book::query()->where('judul', 'Tes MongoDB Compass')->first();
    expect($book)->not->toBeNull();

    $member = Member::query()->create([
        'nama' => 'Alya Test',
        'nim' => '24104077',
        'prodi' => 'Sistem Informasi',
        'email' => 'alya-test@example.com',
        'no_hp' => '081234567077',
        'alamat' => 'Bandung',
        'status' => 'aktif',
    ]);
    expect($member)->not->toBeNull();
    $student = User::factory()->create([
        'name' => 'Alya Test',
        'nama' => 'Alya Test',
        'email' => 'alya-test@example.com',
        'role' => 'mahasiswa',
        'nim' => '24104077',
        'member_id' => Shelfy::id($member),
    ]);

    $this->actingAs($student)->post(route('student.cart.add'), [
        'book_id' => Shelfy::id($book),
        'tanggal_pinjam' => '2026-06-01',
        'tanggal_jatuh_tempo' => '2026-06-08',
        'catatan' => 'Test flow',
    ])->assertRedirect(route('student.cart'));

    $loanResponse = $this->actingAs($student)->post(route('student.checkout'));
    $loanResponse->assertSessionHasNoErrors()->assertRedirect(route('student.loans'));

    $loan = Loan::query()->where('judul_buku', 'Tes MongoDB Compass')->first();
    expect($loan)->not->toBeNull();
    expect($loan->status)->toBe('menunggu_diambil');
    expect($book->refresh()->stok_tersedia)->toBe(1);

    $this->actingAs($user)->post('/returns', [
        'id' => Shelfy::id($loan),
        'tanggal_kembali' => '2026-06-02',
        'from' => 'returns',
    ])->assertRedirect()->assertSessionHas('danger', 'Buku belum dikonfirmasi diambil oleh pustakawan.');

    $this->actingAs($user)->post(route('loans.pickup', Shelfy::id($loan)), [
        'bukti_pengambilan' => 'Kartu anggota fisik',
    ])->assertRedirect()->assertSessionHas('success');

    expect($loan->refresh()->status)->toBe('dipinjam');
    expect($loan->bukti_pengambilan)->toBe('Kartu anggota fisik');

    $response = $this->actingAs($user)->post('/returns', [
        'id' => Shelfy::id($loan),
        'tanggal_kembali' => '2026-06-02',
        'from' => 'returns',
    ]);

    $response
        ->assertRedirect(route('returns.receipt', Shelfy::id($loan)))
        ->assertSessionHas('success');

    expect($loan->refresh()->status)->toBe('dikembalikan');
    expect($loan->nomor_nota)->toStartWith('SHELFY-');
    expect($book->refresh()->stok_tersedia)->toBe(2);

    $this->actingAs($user)
        ->get(route('returns.receipt', Shelfy::id($loan)))
        ->assertOk()
        ->assertSee('Nota Pengembalian')
        ->assertSee('Cetak / Simpan PDF');
});

test('student can pay fine and admin can confirm it from nota', function () {
    $admin = User::factory()->create();
    $member = Member::query()->create([
        'nama' => 'Raka Mahasiswa',
        'nim' => '24104990',
        'prodi' => 'Sistem Informasi',
        'email' => 'raka@example.com',
        'status' => 'aktif',
    ]);
    $student = User::factory()->create([
        'name' => 'Raka Mahasiswa',
        'nama' => 'Raka Mahasiswa',
        'email' => 'raka@example.com',
        'role' => 'mahasiswa',
        'nim' => '24104990',
        'member_id' => Shelfy::id($member),
    ]);
    $loan = Loan::query()->create([
        'book_id' => 'book-test',
        'member_id' => Shelfy::id($member),
        'judul_buku' => 'Struktur Data Mongo',
        'kategori_buku' => 'Teknologi Informasi',
        'nama_anggota' => 'Raka Mahasiswa',
        'nim_anggota' => '24104990',
        'tanggal_pinjam' => '2026-05-01',
        'tanggal_jatuh_tempo' => '2026-05-02',
        'tanggal_kembali' => '2026-06-03',
        'status' => 'dikembalikan',
        'nomor_nota' => 'SHELFY-20260602-TEST99',
        'hari_terlambat' => 2,
        'denda_per_hari' => 2000,
        'total_denda' => 4_000,
        'payment_status' => 'belum_bayar',
    ]);

    $this->actingAs($student)
        ->get(route('returns.receipt', Shelfy::id($loan)))
        ->assertOk()
        ->assertSee('Bayar Denda')
        ->assertDontSee('Konfirmasi Lunas');

    $this->actingAs($student)
        ->post(route('returns.receipt.pay', Shelfy::id($loan)), [
            'payment_method' => 'qris',
        ])
        ->assertRedirect(route('returns.receipt', Shelfy::id($loan)))
        ->assertSessionHas('success');

    $loan->refresh();
    expect($loan->payment_status)->toBe('menunggu_konfirmasi');
    expect($loan->payment_method)->toBe('qris');

    $this->actingAs($admin)
        ->get(route('returns.receipt', Shelfy::id($loan)))
        ->assertOk()
        ->assertSee('Konfirmasi Lunas');

    $this->actingAs($admin)
        ->post(route('returns.receipt.confirm', Shelfy::id($loan)))
        ->assertRedirect(route('returns.receipt', Shelfy::id($loan)))
        ->assertSessionHas('success');

    expect($loan->refresh()->payment_status)->toBe('lunas');
});

test('student sees student area and cannot access admin pages', function () {
    $member = Member::query()->create([
        'nama' => 'Alya Student',
        'nim' => '24104111',
        'prodi' => 'Sistem Informasi',
        'email' => 'alya-student@example.com',
        'status' => 'aktif',
    ]);
    $student = User::factory()->create([
        'name' => 'Alya Student',
        'nama' => 'Alya Student',
        'email' => 'alya-student@example.com',
        'role' => 'mahasiswa',
        'nim' => '24104111',
        'member_id' => Shelfy::id($member),
    ]);

    $this->actingAs($student)->get('/student/dashboard')
        ->assertOk()
        ->assertSee('Beranda Mahasiswa')
        ->assertSee('Bagian Mahasiswa')
        ->assertDontSee('Data Anggota');

    $this->actingAs($student)->get('/members')->assertForbidden();
    $this->actingAs($student)->get('/recap')->assertForbidden();
});

test('inactive student member cannot create loan', function () {
    $book = Book::query()->create([
        'judul' => 'MongoDB Untuk Mahasiswa',
        'penulis' => 'SHELFY',
        'kategori' => 'Teknologi Informasi',
        'stok_total' => 2,
        'stok_tersedia' => 2,
        'dipinjam_count' => 0,
    ]);
    $member = Member::query()->create([
        'nama' => 'Nara Nonaktif',
        'nim' => '24104112',
        'prodi' => 'Sistem Informasi',
        'email' => 'nara-nonaktif@example.com',
        'status' => 'nonaktif',
    ]);
    $student = User::factory()->create([
        'name' => 'Nara Nonaktif',
        'nama' => 'Nara Nonaktif',
        'email' => 'nara-nonaktif@example.com',
        'role' => 'mahasiswa',
        'nim' => '24104112',
        'member_id' => Shelfy::id($member),
    ]);

    $this->actingAs($student)
        ->post(route('student.cart.add'), [
            'book_id' => Shelfy::id($book),
            'tanggal_pinjam' => '2026-06-02',
            'tanggal_jatuh_tempo' => '2026-06-09',
        ])
        ->assertRedirect()
        ->assertSessionHas('danger', 'Anggota tidak aktif, tidak bisa membuat peminjaman.');

    expect(Loan::query()->where('nim_anggota', '24104112')->count())->toBe(0);
    expect($book->refresh()->stok_tersedia)->toBe(2);
});

test('student can add books to cart checkout and open loan detail', function () {
    $book = Book::query()->create([
        'judul' => 'Katalog Mongo Populer',
        'penulis' => 'SHELFY',
        'kategori' => 'Teknologi Informasi',
        'stok_total' => 3,
        'stok_tersedia' => 3,
        'dipinjam_count' => 4,
    ]);
    $member = Member::query()->create([
        'nama' => 'Dina Mahasiswa',
        'nim' => '24104113',
        'prodi' => 'Sistem Informasi',
        'email' => 'dina@example.com',
        'status' => 'aktif',
    ]);
    $student = User::factory()->create([
        'name' => 'Dina Mahasiswa',
        'nama' => 'Dina Mahasiswa',
        'email' => 'dina@example.com',
        'role' => 'mahasiswa',
        'nim' => '24104113',
        'member_id' => Shelfy::id($member),
    ]);

    $this->actingAs($student)
        ->post(route('student.cart.add'), [
            'book_id' => Shelfy::id($book),
            'tanggal_pinjam' => '2026-06-03',
            'tanggal_jatuh_tempo' => '2026-06-10',
            'catatan' => 'Checkout test',
        ])
        ->assertRedirect(route('student.cart'))
        ->assertSessionHas('success');

    expect(CartItem::query()->where('user_id', Shelfy::id($student))->count())->toBe(1);

    $this->actingAs($student)
        ->post(route('student.checkout'))
        ->assertRedirect(route('student.loans'))
        ->assertSessionHas('success');

    $loan = Loan::query()->where('nim_anggota', '24104113')->first();
    expect($loan)->not->toBeNull();
    expect($loan->status)->toBe('menunggu_diambil');
    expect($book->refresh()->stok_tersedia)->toBe(2);
    expect(CartItem::query()->where('user_id', Shelfy::id($student))->count())->toBe(0);

    $this->actingAs($student)
        ->get(route('student.loans.show', Shelfy::id($loan)))
        ->assertOk()
        ->assertSee('Detail Peminjaman')
        ->assertSee('Menunggu Diambil');
});

test('admin and pustakawan can process pickup', function () {
    $book = Book::query()->create([
        'judul' => 'Role Pustakawan',
        'penulis' => 'SHELFY',
        'kategori' => 'Referensi',
        'stok_total' => 1,
        'stok_tersedia' => 0,
        'dipinjam_count' => 1,
    ]);
    $member = Member::query()->create([
        'nama' => 'Bima Role',
        'nim' => '24104114',
        'email' => 'bima@example.com',
        'status' => 'aktif',
    ]);
    $loan = Loan::query()->create([
        'book_id' => Shelfy::id($book),
        'member_id' => Shelfy::id($member),
        'judul_buku' => $book->judul,
        'kategori_buku' => $book->kategori,
        'nama_anggota' => $member->nama,
        'nim_anggota' => $member->nim,
        'tanggal_pinjam' => '2026-06-03',
        'tanggal_jatuh_tempo' => '2026-06-10',
        'status' => 'menunggu_diambil',
    ]);
    $secondLoan = Loan::query()->create([
        'book_id' => Shelfy::id($book),
        'member_id' => Shelfy::id($member),
        'judul_buku' => $book->judul,
        'kategori_buku' => $book->kategori,
        'nama_anggota' => $member->nama,
        'nim_anggota' => $member->nim,
        'tanggal_pinjam' => '2026-06-03',
        'tanggal_jatuh_tempo' => '2026-06-10',
        'status' => 'menunggu_diambil',
    ]);
    $pustakawan = User::factory()->create(['role' => 'pustakawan']);
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Admin');

    $this->actingAs($admin)
        ->post(route('loans.pickup', Shelfy::id($loan)), ['bukti_pengambilan' => 'Admin cek'])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($loan->refresh()->status)->toBe('dipinjam');

    $this->actingAs($pustakawan)
        ->post(route('loans.pickup', Shelfy::id($secondLoan)), ['bukti_pengambilan' => 'Pustakawan cek'])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($secondLoan->refresh()->status)->toBe('dipinjam');
    expect($secondLoan->petugas_pengambilan)->toBe($pustakawan->displayName());
});
