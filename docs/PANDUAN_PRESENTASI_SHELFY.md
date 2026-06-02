# Panduan Presentasi SHELFY Laravel

SHELFY adalah aplikasi perpustakaan digital berbasis Laravel Breeze dan MongoDB. Aplikasi ini membantu pengelolaan buku, anggota, peminjaman, pengembalian, rekapitulasi, login/register, role admin/mahasiswa, dan nota pengembalian.

## Alur Demo Disarankan

1. Buka aplikasi:

```text
http://127.0.0.1:8000
```

2. Login sebagai admin.
3. Tunjukkan Dashboard:
   jumlah buku, stok total, stok tersedia, dipinjam, terlambat, dan panel MongoDB aktif.
4. Buka Buku:
   tambah buku, edit buku, hapus buku jika tidak sedang dipinjam, filter pencarian/kategori/status.
5. Buka Anggota:
   tambah anggota, edit anggota, hapus anggota jika tidak punya peminjaman aktif, filter status.
6. Buka Peminjaman:
   pilih buku tersedia dan anggota aktif.
   Setelah simpan, stok buku berkurang.
7. Buka Pengembalian:
   proses pengembalian.
   Setelah simpan, stok buku bertambah dan langsung masuk halaman nota.
8. Buka Nota:
   tunjukkan nomor nota, total denda, status pembayaran, dan tombol Cetak / Simpan PDF.
9. Login sebagai mahasiswa:
   mahasiswa hanya melihat Beranda, Katalog Buku, Peminjaman Saya, Riwayat & Nota, dan Profil.
10. Dari nota mahasiswa:
   jika ada denda, mahasiswa klik Bayar Denda.
11. Login admin lagi:
   admin membuka nota yang sama lalu klik Konfirmasi Lunas.
12. Buka Rekap:
   tunjukkan SUM, AVG, Greater Than, top buku dipinjam, dan peminjaman per kategori.
13. Buka MongoDB Compass:
   tunjukkan collection `users`, `books`, `members`, dan `loans`.

## Backend Yang Perlu Dijelaskan

### Auth dan Role

Laravel Breeze dipakai untuk login, register, logout, dan profile. Model `User` diganti ke MongoDB authenticatable.

File:

```text
app\Models\User.php
app\Http\Middleware\EnsureAdmin.php
app\Http\Middleware\EnsureStudent.php
routes\web.php
```

Admin diarahkan ke:

```text
/dashboard
```

Mahasiswa diarahkan ke:

```text
/student/dashboard
```

### MVC

Controller utama:

```text
BookController
MemberController
LoanController
ReturnController
ReceiptController
RecapController
StudentController
```

Model MongoDB:

```text
User
Book
Member
Loan
```

View Blade:

```text
resources\views\shelfy
resources\views\layouts
resources\views\auth
resources\views\profile
```

### Cara Memanggil MongoDB Di Laravel

Contoh ambil buku tersedia:

```php
Book::query()
    ->where('stok_tersedia', '>', 0)
    ->orderBy('judul')
    ->get();
```

Contoh simpan peminjaman:

```php
Loan::query()->create([
    'book_id' => $bookId,
    'member_id' => $memberId,
    'status' => 'dipinjam',
]);
```

Contoh rekap stok:

```php
$stokTotal = $books->sum(fn ($book) => (int) ($book->stok_total ?? 0));
$avgStok = $books->avg(fn ($book) => (int) ($book->stok_total ?? 0));
$gtFive = $books->filter(fn ($book) => (int) ($book->stok_total ?? 0) > 5)->count();
```

## File Penting Untuk Ditunjukkan

```text
.env
config\database.php
routes\web.php
app\Support\Shelfy.php
app\Support\ShelfySeeder.php
app\Http\Controllers\ReceiptController.php
resources\views\shelfy\receipts\show.blade.php
```

## Perintah Cek Sebelum Presentasi

```bash
php artisan route:list --except-vendor
php artisan test
cmd /c npm run build
php artisan db:seed
php artisan serve --host=127.0.0.1 --port=8000
```

## Jawaban Singkat Jika Dosen Bertanya

### Kenapa MongoDB?

Karena tugas wajib memakai database NoSQL. SHELFY memakai MongoDB untuk menyimpan data sebagai dokumen collection, bukan tabel SQL.

### Apakah ada CRUD?

Ada. Admin bisa CRUD buku dan anggota. Mahasiswa bisa membuat peminjaman untuk akunnya.

### Apakah ada filter?

Ada. Buku, anggota, dan peminjaman bisa dicari dan difilter.

### Apakah ada rekapitulasi?

Ada. Rekap menampilkan SUM stok, AVG stok, Greater Than stok lebih dari 5, buku paling sering dipinjam, dan jumlah peminjaman per kategori.

### Bagaimana nota PDF dibuat?

Nota dibuat sebagai halaman Blade yang print-friendly. Untuk PDF, klik Cetak / Simpan PDF lalu pilih Save as PDF atau Microsoft Print to PDF.
