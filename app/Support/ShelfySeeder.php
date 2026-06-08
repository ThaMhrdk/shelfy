<?php

namespace App\Support;

use App\Models\Book;
use App\Models\Loan;
use App\Models\Member;
use App\Models\User;

class ShelfySeeder
{
    public static function ensureAdmin(): void
    {
        $users = [
            [
                'name' => 'Admin SHELFY',
                'email' => User::DEFAULT_ADMIN_EMAIL,
                'password' => User::DEFAULT_ADMIN_PASSWORD,
                'role' => 'admin',
            ],
            [
                'name' => 'Pustakawan SHELFY',
                'email' => 'pustakawan@shelfy.test',
                'password' => 'pustaka123',
                'role' => 'pustakawan',
            ],
            [
                'name' => 'Admin Kepala SHELFY',
                'email' => 'kepala@shelfy.test',
                'password' => 'kepala123',
                'role' => 'admin',
            ],
        ];

        foreach ($users as $row) {
            User::query()->updateOrCreate(['email' => $row['email']], [
                'name' => $row['name'],
                'nama' => $row['name'],
                'password' => $row['password'],
                'role' => $row['role'],
                'status' => 'aktif',
            ]);
        }
    }

    public static function seedDemo(): string
    {
        self::ensureAdmin();

        if (Book::query()->count() > 0 || Member::query()->count() > 0) {
            return 'Seed dilewati karena data buku/anggota sudah ada.';
        }

        $bookRows = [
            ['Basis Data: Konsep dan Aplikasi', 'Ramez Elmasri', 'Teknologi Informasi', 'Informatika', 2022, '9786021514001', 12],
            ['Sistem Informasi Manajemen', 'Kenneth Laudon', 'Manajemen', 'Salemba Empat', 2021, '9789790610002', 8],
            ['Pemrograman Java', 'Harvey Deitel', 'Teknologi Informasi', 'Pearson', 2020, '9780134670942', 5],
            ['Struktur Data dengan C', 'Seymour Lipschutz', 'Teknologi Informasi', 'McGraw Hill', 2019, '9780070380012', 3],
            ['Pengantar Ekonomi Mikro', 'Sadono Sukirno', 'Ekonomi', 'Rajawali Pers', 2020, '9789797690007', 7],
            ['Metodologi Penelitian', 'Sugiyono', 'Referensi', 'Alfabeta', 2021, '9786022890001', 6],
        ];

        $books = [];

        foreach ($bookRows as [$title, $author, $category, $publisher, $year, $isbn, $stock]) {
            $books[$title] = Book::query()->create([
                'judul' => $title,
                'penulis' => $author,
                'kategori' => $category,
                'penerbit' => $publisher,
                'tahun' => $year,
                'isbn' => $isbn,
                'stok_total' => $stock,
                'stok_tersedia' => $stock,
                'dipinjam_count' => 0,
                'deskripsi' => 'Data contoh migrasi SHELFY dari native ke Laravel Breeze.',
                'cover_path' => null,
            ]);
        }

        $memberRows = [
            ['Alya Putri', '24104001', 'Sistem Informasi', 'alya@example.com', '081234567001', 'Bandung'],
            ['Bima Pratama', '24104002', 'Informatika', 'bima@example.com', '081234567002', 'Cimahi'],
            ['Citra Lestari', '24104003', 'Sistem Informasi', 'citra@example.com', '081234567003', 'Sumedang'],
            ['Dimas Akbar', '24104004', 'Manajemen Informatika', 'dimas@example.com', '081234567004', 'Garut'],
        ];

        $members = [];

        foreach ($memberRows as [$name, $nim, $prodi, $email, $phone, $address]) {
            $members[$name] = Member::query()->create([
                'nama' => $name,
                'nim' => $nim,
                'prodi' => $prodi,
                'email' => $email,
                'no_hp' => $phone,
                'alamat' => $address,
                'status' => 'aktif',
            ]);
        }

        $loanRows = [
            ['Basis Data: Konsep dan Aplikasi', 'Alya Putri', '-8 days', '+2 days', 'dipinjam'],
            ['Sistem Informasi Manajemen', 'Bima Pratama', '-12 days', '-2 days', 'terlambat'],
            ['Pemrograman Java', 'Citra Lestari', '-4 days', '+5 days', 'dipinjam'],
            ['Basis Data: Konsep dan Aplikasi', 'Dimas Akbar', '-20 days', '-10 days', 'dikembalikan'],
        ];

        foreach ($loanRows as [$bookTitle, $memberName, $borrowMod, $dueMod, $status]) {
            $book = $books[$bookTitle];
            $member = $members[$memberName];
            $borrowDate = now()->modify($borrowMod)->format('Y-m-d');
            $dueDate = now()->modify($dueMod)->format('Y-m-d');
            $returnDate = now()->format('Y-m-d');
            $fine = $status === 'dikembalikan'
                ? Shelfy::lateFee($dueDate, $returnDate)
                : ['hari_terlambat' => 0, 'denda_per_hari' => Shelfy::FINE_PER_DAY, 'total_denda' => 0];

            Loan::query()->create([
                'book_id' => Shelfy::id($book),
                'member_id' => Shelfy::id($member),
                'judul_buku' => $book->judul,
                'kategori_buku' => $book->kategori,
                'nama_anggota' => $member->nama,
                'nim_anggota' => $member->nim,
                'tanggal_pinjam' => $borrowDate,
                'tanggal_jatuh_tempo' => $dueDate,
                'tanggal_kembali' => $status === 'dikembalikan' ? $returnDate : null,
                'status' => $status,
                'catatan' => 'Data contoh peminjaman migrasi.',
                'dibuat_oleh' => 'Seeder SHELFY',
                'petugas_pengembalian' => $status === 'dikembalikan' ? 'Admin SHELFY' : null,
                'hari_terlambat' => $fine['hari_terlambat'],
                'denda_per_hari' => $fine['denda_per_hari'],
                'total_denda' => $fine['total_denda'],
                'payment_status' => $fine['total_denda'] > 0 ? 'belum_bayar' : 'lunas',
            ]);

            $book->increment('dipinjam_count');

            if ($status !== 'dikembalikan') {
                $book->decrement('stok_tersedia');
            }
        }

        return 'Seed berhasil: buku, anggota, dan peminjaman contoh sudah dibuat.';
    }
}
