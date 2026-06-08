<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\CartItem;
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
                'topBooks' => Shelfy::topBorrowed(Book::query()->orderBy('judul')->get()),
                'mongoError' => null,
            ]);
        } catch (Throwable $e) {
            return view('shelfy.student.dashboard', [
                'studentStats' => $this->studentStats(collect()),
                'books' => collect(),
                'loans' => collect(),
                'topBooks' => collect(),
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
                'cartBookIds' => $this->cartItems($request->user())->pluck('book_id')->all(),
                'mongoError' => null,
            ]);
        } catch (Throwable $e) {
            return view('shelfy.student.books', [
                'books' => collect(),
                'categories' => $this->categories(collect()),
                'cartBookIds' => [],
                'mongoError' => $e->getMessage(),
            ]);
        }
    }

    public function bookDetail(Request $request, string $id): View
    {
        $book = Book::query()->findOrFail($id);

        return view('shelfy.student.book-detail', [
            'book' => $book,
            'alreadyInCart' => $this->cartItems($request->user())->contains('book_id', $id),
        ]);
    }

    public function cart(Request $request): View
    {
        $items = $this->cartItems($request->user());
        $bookIds = $items->pluck('book_id')->all();

        return view('shelfy.student.cart', [
            'cartItems' => $items,
            'books' => Book::query()
                ->get()
                ->filter(fn ($book) => in_array(Shelfy::id($book), $bookIds, true))
                ->keyBy(fn ($book) => Shelfy::id($book)),
        ]);
    }

    public function loans(Request $request): View
    {
        try {
            $loans = $this->filterLoans($this->studentLoans($request->user()), $request);

            return view('shelfy.student.loans', [
                'loans' => $loans,
                'mongoError' => null,
            ]);
        } catch (Throwable $e) {
            return view('shelfy.student.loans', [
                'loans' => collect(),
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

    public function loanDetail(Request $request, string $id): View
    {
        $loan = Loan::query()->findOrFail($id);

        abort_unless(Shelfy::loanBelongsToUser($loan, $request->user()), 403, 'Peminjaman ini bukan milik akun kamu.');

        return view('shelfy.student.loan-detail', [
            'loan' => $loan,
        ]);
    }

    public function addToCart(Request $request): RedirectResponse
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

        if (($member->status ?? 'aktif') !== 'aktif') {
            return back()->with('danger', 'Anggota tidak aktif, tidak bisa membuat peminjaman.');
        }

        $book = Book::query()->findOrFail($validated['book_id']);

        if ((int) ($book->stok_tersedia ?? 0) < 1) {
            return back()->with('danger', 'Stok buku sedang habis.');
        }

        $payload = [
            'user_id' => Shelfy::id($request->user()),
            'member_id' => Shelfy::id($member),
            'book_id' => Shelfy::id($book),
            'judul_buku' => $book->judul,
            'kategori_buku' => $book->kategori,
            'tanggal_pinjam' => $validated['tanggal_pinjam'],
            'tanggal_jatuh_tempo' => $validated['tanggal_jatuh_tempo'],
            'catatan' => $validated['catatan'] ?? '',
        ];

        $existing = CartItem::query()
            ->where('user_id', Shelfy::id($request->user()))
            ->where('book_id', Shelfy::id($book))
            ->first();

        $existing ? $existing->update($payload) : CartItem::query()->create($payload);

        return redirect()->route('student.cart')->with('success', 'Buku berhasil masuk keranjang.');
    }

    public function removeCart(Request $request, string $id): RedirectResponse
    {
        $item = CartItem::query()
            ->where('_id', $id)
            ->where('user_id', Shelfy::id($request->user()))
            ->firstOrFail();

        $item->delete();

        return back()->with('success', 'Buku dihapus dari keranjang.');
    }

    public function checkout(Request $request): RedirectResponse
    {
        try {
            $member = $this->memberForUser($request->user());
        } catch (RuntimeException $e) {
            return back()->with('danger', $e->getMessage());
        }

        if (($member->status ?? 'aktif') !== 'aktif') {
            return back()->with('danger', 'Anggota tidak aktif, tidak bisa membuat peminjaman.');
        }

        $items = $this->cartItems($request->user());

        if ($items->isEmpty()) {
            return back()->with('danger', 'Keranjang masih kosong.');
        }

        foreach ($items as $item) {
            $book = Book::query()->find($item->book_id);

            if (! $book || (int) ($book->stok_tersedia ?? 0) < 1) {
                return back()->with('danger', 'Ada buku yang stoknya sudah habis. Periksa keranjang lagi.');
            }
        }

        foreach ($items as $item) {
            $book = Book::query()->findOrFail($item->book_id);

            Loan::query()->create([
                'book_id' => Shelfy::id($book),
                'member_id' => Shelfy::id($member),
                'judul_buku' => $book->judul,
                'kategori_buku' => $book->kategori,
                'nama_anggota' => $member->nama,
                'nim_anggota' => $member->nim,
                'tanggal_pinjam' => $item->tanggal_pinjam,
                'tanggal_jatuh_tempo' => $item->tanggal_jatuh_tempo,
                'tanggal_kembali' => null,
                'status' => 'menunggu_diambil',
                'catatan' => $item->catatan ?? '',
                'dibuat_oleh' => Auth::user()?->displayName() ?? 'Mahasiswa',
                'hari_terlambat' => 0,
                'denda_per_hari' => Shelfy::FINE_PER_DAY,
                'total_denda' => 0,
                'payment_status' => null,
            ]);

            $book->decrement('stok_tersedia');
            $book->increment('dipinjam_count');
            $item->delete();
        }

        return redirect()->route('student.loans')->with('success', 'Checkout berhasil. Tunjukkan halaman peminjaman ke pustakawan saat mengambil buku.');
    }

    private function studentLoans(?object $user): Collection
    {
        return Shelfy::filterLoansForUser(
            Loan::query()->orderBy('created_at', 'desc')->get(),
            $user
        );
    }

    private function cartItems(?object $user): Collection
    {
        return CartItem::query()
            ->where('user_id', Shelfy::id($user))
            ->orderBy('created_at', 'desc')
            ->get();
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
            'aktif' => $loans->whereIn('status', ['menunggu_diambil', 'dipinjam', 'terlambat'])->count(),
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
