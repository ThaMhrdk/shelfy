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
                'email' => 'pustakawan@gmail.com',
                'password' => 'pustaka123',
                'role' => 'pustakawan',
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
            ['Sistem Tata Kelola Kota Cerdas', 'Bu Febi', 'Sistem Informasi Kota Cerdas', 'Fakultas Ilmu Terapan', 2024, '9786021514001', 12],
            ['Basis Data II', 'Bu Maria', 'Sistem Informasi Kota Cerdas', 'Fakultas Ilmu Terapan', 2024, '9789790610002', 8],
            ['Proyeksi Inovasi Kota Cerdas', 'Pak Asad', 'Sistem Informasi Kota Cerdas', 'Fakultas Ilmu Terapan', 2024, '9780134670942', 5],
            ['Dasar Ilmu Data', 'Bu Vivi', 'Sistem Informasi Kota Cerdas', 'Fakultas Ilmu Terapan', 2024, '9780070380012', 3],
            ['Visualisasi Data', 'Bu Vivi', 'Sistem Informasi Kota Cerdas', 'Fakultas Ilmu Terapan', 2024, '9789797690007', 7],
            ['Pancasila', 'Pak Bambang', 'Umum', 'Telkom University', 2024, '9786022890001', 6],
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
                'deskripsi' => 'Data contoh katalog SHELFY untuk TUBES Basis Data II berbasis MongoDB.',
                'cover_path' => null,
            ]);
        }

        $memberRows = [
            ['Michael Eluzai', '70701240001', 'D4 Sistem Informasi Kota Cerdas', 'michael@gmail.com', '08123456789', 'Jalan Cikampek'],
            ['Mumpuni Nur Idzati', '70701240002', 'D4 Sistem Informasi Kota Cerdas', 'mumpuni@gmail.com', '080808080808', 'Jalan PGA'],
            ['Muhammad Fadhil Athallah', '70701240003', 'D4 Sistem Informasi Kota Cerdas', 'fadhil@gmail.com', '08234567891', 'Jalan Sukapura'],
            ['Muhammad Anantha Mahardika Ridwan', '707012400122', 'D4 Sistem Informasi Kota Cerdas', 'anantha@gmail.com', '082157584633', 'Jalan Buah Batu'],
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

            $user = User::query()->create([
                'name' => $name,
                'nama' => $name,
                'email' => $email,
                'password' => 'mahasiswa123',
                'role' => 'mahasiswa',
                'nim' => $nim,
                'prodi' => $prodi,
                'no_hp' => $phone,
                'alamat' => $address,
                'member_id' => Shelfy::id($members[$name]),
                'status' => 'aktif',
            ]);

            $members[$name]->update(['user_id' => Shelfy::id($user)]);
        }

        $loanRows = [
            ['Visualisasi Data', 'Michael Eluzai', '-2 days', '+5 days', 'dipinjam'],
            ['Dasar Ilmu Data', 'Mumpuni Nur Idzati', '-4 days', '+3 days', 'menunggu_diambil'],
            ['Basis Data II', 'Muhammad Fadhil Athallah', '-12 days', '-2 days', 'terlambat'],
            ['Sistem Tata Kelola Kota Cerdas', 'Muhammad Anantha Mahardika Ridwan', '-20 days', '-10 days', 'dikembalikan'],
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
                'tanggal_diambil' => $status === 'menunggu_diambil' ? null : $borrowDate . ' 09:00:00',
                'bukti_pengambilan' => $status === 'menunggu_diambil' ? null : 'Kartu mahasiswa diverifikasi.',
                'petugas_pengambilan' => $status === 'menunggu_diambil' ? null : 'Pustakawan SHELFY',
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
