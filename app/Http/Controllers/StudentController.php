<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Loan;
use App\Models\Member;
use App\Support\Shelfy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class StudentController extends Controller
{
    public function dashboard(Request $request): View
    {
        try {
            $books = $this->availableBooks();
            $loans = $this->studentLoans($request->user());

            return view('shelfy.student.dashboard', [
                'studentStats' => $this->studentStats($loans),
                'books' => $books,
                'loans' => $loans->take(5),
                'mongoError' => null,
            ]);
        } catch (Throwable $e) {
            return view('shelfy.student.dashboard', [
                'studentStats' => $this->studentStats(collect()),
                'books' => collect(),
                'loans' => collect(),
                'mongoError' => $e->getMessage(),
            ]);
        }
    }

    public function books(Request $request): View
    {
        try {
            $allBooks = Book::query()->orderBy('created_at', 'desc')->get();

            return view('shelfy.student.books', [
                'books' => $this->filterBooks($allBooks, $request),
                'categories' => $this->categories($allBooks),
                'mongoError' => null,
            ]);
        } catch (Throwable $e) {
            return view('shelfy.student.books', [
                'books' => collect(),
                'categories' => $this->categories(collect()),
                'mongoError' => $e->getMessage(),
            ]);
        }
    }

    public function loans(Request $request): View
    {
        try {
            $loans = $this->filterLoans($this->studentLoans($request->user()), $request);

            return view('shelfy.student.loans', [
                'loans' => $loans,
                'availableBooks' => $this->availableBooks(),
                'mongoError' => null,
            ]);
        } catch (Throwable $e) {
            return view('shelfy.student.loans', [
                'loans' => collect(),
                'availableBooks' => collect(),
                'mongoError' => $e->getMessage(),
            ]);
        }
    }

    public function history(Request $request): View
    {
        try {
            $historyLoans = $this->studentLoans($request->user())
                ->where('status', 'dikembalikan')
                ->values();

            return view('shelfy.student.history', [
                'historyLoans' => $historyLoans,
                'mongoError' => null,
            ]);
        } catch (Throwable $e) {
            return view('shelfy.student.history', [
                'historyLoans' => collect(),
                'mongoError' => $e->getMessage(),
            ]);
        }
    }

    public function storeLoan(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'book_id' => ['required', 'string'],
            'tanggal_pinjam' => ['required', 'date'],
            'tanggal_jatuh_tempo' => ['required', 'date'],
            'catatan' => ['nullable', 'string'],
        ]);

        try {
            $member = $this->memberForUser($request->user());
        } catch (RuntimeException $e) {
            return back()->with('danger', $e->getMessage());
        }

        $book = Book::query()->findOrFail($validated['book_id']);

        if (($member->status ?? 'aktif') !== 'aktif') {
            return back()->with('danger', 'Anggota tidak aktif, tidak bisa membuat peminjaman.');
        }

        if ((int) ($book->stok_tersedia ?? 0) < 1) {
            return back()->with('danger', 'Stok buku sedang habis.');
        }

        Loan::query()->create([
            'book_id' => Shelfy::id($book),
            'member_id' => Shelfy::id($member),
            'judul_buku' => $book->judul,
            'kategori_buku' => $book->kategori,
            'nama_anggota' => $member->nama,
            'nim_anggota' => $member->nim,
            'tanggal_pinjam' => $validated['tanggal_pinjam'],
            'tanggal_jatuh_tempo' => $validated['tanggal_jatuh_tempo'],
            'tanggal_kembali' => null,
            'status' => $validated['tanggal_jatuh_tempo'] < date('Y-m-d') ? 'terlambat' : 'dipinjam',
            'catatan' => $validated['catatan'] ?? '',
            'dibuat_oleh' => Auth::user()?->displayName() ?? 'Mahasiswa',
            'hari_terlambat' => 0,
            'denda_per_hari' => Shelfy::FINE_PER_DAY,
            'total_denda' => 0,
            'payment_status' => null,
        ]);

        $book->decrement('stok_tersedia');
        $book->increment('dipinjam_count');

        return redirect()->route('student.loans')->with('success', 'Pengajuan peminjaman berhasil dicatat.');
    }

    private function studentLoans(?object $user): Collection
    {
        return Shelfy::filterLoansForUser(
            Loan::query()->orderBy('created_at', 'desc')->get(),
            $user
        );
    }

    private function availableBooks(): Collection
    {
        return Book::query()
            ->where('stok_tersedia', '>', 0)
            ->orderBy('judul')
            ->get();
    }

    private function memberForUser(?object $user): Member
    {
        $member = null;

        if ($user?->member_id) {
            $member = Member::query()->find((string) $user->member_id);
        }

        if (! $member && $user?->nim) {
            $member = Member::query()->where('nim', (string) $user->nim)->first();
        }

        if (! $member && $user?->nim) {
            $member = Member::query()->create([
                'nama' => $user->displayName(),
                'nim' => $user->nim,
                'prodi' => $user->prodi,
                'email' => $user->email,
                'no_hp' => $user->no_hp,
                'alamat' => $user->alamat,
                'status' => 'aktif',
                'user_id' => Shelfy::id($user),
            ]);

            $user->update(['member_id' => Shelfy::id($member)]);
        }

        if (! $member) {
            throw new RuntimeException('Akun mahasiswa belum terhubung ke data anggota.');
        }

        return $member;
    }

    private function studentStats(Collection $loans): array
    {
        return [
            'aktif' => $loans->whereIn('status', ['dipinjam', 'terlambat'])->count(),
            'terlambat' => $loans->where('status', 'terlambat')->count(),
            'selesai' => $loans->where('status', 'dikembalikan')->count(),
        ];
    }

    private function filterBooks(Collection $books, Request $request): Collection
    {
        $q = (string) $request->query('q', '');
        $category = (string) $request->query('kategori', '');
        $status = (string) $request->query('status', '');

        return $books
            ->filter(fn ($book) => Shelfy::containsText($book, ['judul', 'penulis', 'isbn'], $q))
            ->filter(fn ($book) => $category === '' || (string) $book->kategori === $category)
            ->filter(fn ($book) => $status === '' || $book->status() === $status)
            ->values();
    }

    private function filterLoans(Collection $loans, Request $request): Collection
    {
        $q = (string) $request->query('q', '');
        $status = (string) $request->query('status', '');

        return $loans
            ->filter(fn ($loan) => Shelfy::containsText($loan, ['judul_buku', 'nama_anggota', 'nim_anggota'], $q))
            ->filter(fn ($loan) => $status === '' || (string) $loan->status === $status)
            ->values();
    }

    private function categories(Collection $books): array
    {
        return $books
            ->pluck('kategori')
            ->merge(['Teknologi Informasi', 'Manajemen', 'Ekonomi', 'Sastra', 'Referensi'])
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }
}
