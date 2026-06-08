# Panduan MongoDB Compass SHELFY

Dokumen ini dipakai untuk menjelaskan setup database SHELFY saat presentasi Basis Data II.

## Koneksi Compass

1. Buka MongoDB Compass.
2. Isi URI:

```text
mongodb://127.0.0.1:27017
```

3. Klik Connect.
4. Pilih database:

```text
shelfy_db
```

Jika database belum ada, jalankan seeder Laravel:

```bash
php artisan db:seed
```

## Konfigurasi Laravel

File utama:

```text
D:\Program Files\xampp\htdocs\laravel\shelfy\.env
```

Nilai penting:

```env
DB_CONNECTION=mongodb
DB_HOST=127.0.0.1
DB_PORT=27017
DB_DATABASE=shelfy_db
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

Laravel memakai package:

```text
mongodb/laravel-mongodb
```

Model yang memakai MongoDB:

```text
app\Models\User.php
app\Models\Book.php
app\Models\Member.php
app\Models\Loan.php
```

## Collection Utama

### users

Fungsi: data login, register, role admin/mahasiswa, dan relasi akun mahasiswa ke anggota.

Field penting:

```text
_id, name, nama, email, password, role, nim, prodi, member_id, status
```

### books

Fungsi: master data buku dan stok.

Field penting:

```text
_id, judul, penulis, kategori, penerbit, tahun, isbn,
stok_total, stok_tersedia, dipinjam_count
```

### members

Fungsi: data anggota/mahasiswa perpustakaan.

Field penting:

```text
_id, nama, nim, prodi, email, no_hp, alamat, status
```

### loans

Fungsi: transaksi peminjaman, pengembalian, denda, nota, dan status pembayaran.

Field penting:

```text
_id, book_id, member_id, judul_buku, kategori_buku,
nama_anggota, nim_anggota, tanggal_pinjam, tanggal_jatuh_tempo,
tanggal_kembali, status, nomor_nota, hari_terlambat,
denda_per_hari, total_denda, payment_status, payment_method,
payment_reference, confirmed_by
```

## Kenapa Database Ini Termasuk Kompleks

1. Ada pembagian role:
   admin mengelola data buku, anggota, peminjaman, pengembalian, dan rekap.
   mahasiswa hanya melihat katalog, mengajukan peminjaman, melihat riwayat, dan nota.

2. Ada relasi dokumen tanpa JOIN:
   `loans.book_id` mengarah ke `books._id`.
   `loans.member_id` mengarah ke `members._id`.
   Data nama buku dan anggota ikut disalin ke `loans` sebagai snapshot histori.

3. Ada CRUD:
   buku dan anggota bisa tambah, edit, hapus, dan filter.

4. Ada filter:
   buku difilter berdasarkan pencarian, kategori, dan status stok.
   anggota difilter berdasarkan pencarian dan status.
   peminjaman difilter berdasarkan pencarian dan status.

5. Ada rekapitulasi:
   SUM stok, AVG stok, stok greater than 5, buku paling sering dipinjam, dan jumlah peminjaman per kategori.

6. Ada transaksi:
   peminjaman mengurangi `stok_tersedia`.
   pengembalian menambah `stok_tersedia`.
   buku terlambat otomatis memiliki denda.

7. Ada nota:
   pengembalian menghasilkan `nomor_nota`.
   nota bisa dicetak lewat browser dengan pilihan Save as PDF atau Microsoft Print to PDF.

## Contoh Query Yang Bisa Ditunjukkan Di Compass

Cari buku yang masih tersedia:

```json
{ "stok_tersedia": { "$gt": 0 } }
```

Cari peminjaman yang terlambat:

```json
{ "status": "terlambat" }
```

Cari nota yang belum bayar:

```json
{ "status": "dikembalikan", "payment_status": "belum_bayar" }
```

## Contoh Aggregation

Rekap stok buku:

```json
[
  {
    "$group": {
      "_id": null,
      "total_buku": { "$sum": 1 },
      "total_stok": { "$sum": "$stok_total" },
      "rata_rata_stok": { "$avg": "$stok_total" }
    }
  }
]
```

Buku paling sering dipinjam:

```json
[
  { "$sort": { "dipinjam_count": -1 } },
  { "$limit": 5 }
]
```

Jumlah peminjaman per kategori:

```json
[
  {
    "$group": {
      "_id": "$kategori_buku",
      "total": { "$sum": 1 }
    }
  },
  { "$sort": { "total": -1 } }
]
```

## Akun Demo

Akun ini dibuat lewat seeder, bukan ditampilkan di halaman login.

```text
Admin:
admin@gmail.com / admin123

Pustakawan:
pustakawan@gmail.com / pustaka123

Mahasiswa:
michael@gmail.com / mahasiswa123
```
