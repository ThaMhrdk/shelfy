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
use Throwable;

class LoanController extends Controller
{
    public function index(Request $request): View
    {
        try {
            $this->refreshOverdue();
            $user = $request->user();
            $loans = Shelfy::filterLoansForUser(
                Loan::query()->orderBy('created_at', 'desc')->get(),
                $user
            );

            return view('shelfy.loans.index', [
                'loans' => $this->filterLoans($loans, $request),
                'availableBooks' => $user?->isAdmin() ? Book::query()->where('stok_tersedia', '>', 0)->orderBy('judul')->get() : collect(),
                'activeMembers' => $user?->isAdmin() ? Member::query()->where('status', 'aktif')->orderBy('nama')->get() : collect(),
                'isAdmin' => $user?->isAdmin(),
                'mongoError' => null,
            ]);
        } catch (Throwable $e) {
            return view('shelfy.loans.index', [
                'loans' => collect(),
                'availableBooks' => collect(),
                'activeMembers' => collect(),
                'isAdmin' => false,
                'mongoError' => $e->getMessage(),
            ]);
        }
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403, 'Halaman ini khusus bagian admin.');

        $validated = $request->validate([
            'book_id' => ['required', 'string'],
            'member_id' => ['required', 'string'],
            'tanggal_pinjam' => ['required', 'date'],
            'tanggal_jatuh_tempo' => ['required', 'date'],
            'catatan' => ['nullable', 'string'],
        ]);

        $book = Book::query()->findOrFail($validated['book_id']);
        $member = Member::query()->findOrFail($validated['member_id']);

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
            'dibuat_oleh' => Auth::user()?->name ?? 'Admin SHELFY',
            'hari_terlambat' => 0,
            'denda_per_hari' => Shelfy::FINE_PER_DAY,
            'total_denda' => 0,
            'payment_status' => null,
        ]);

        $book->decrement('stok_tersedia');
        $book->increment('dipinjam_count');

        return redirect()->route('loans.index')->with('success', 'Peminjaman berhasil dicatat.');
    }

    private function refreshOverdue(): void
    {
        Loan::query()
            ->where('status', 'dipinjam')
            ->where('tanggal_jatuh_tempo', '<', date('Y-m-d'))
            ->get()
            ->each(fn ($loan) => $loan->update(['status' => 'terlambat']));
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
}
