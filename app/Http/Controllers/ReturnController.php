<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Loan;
use App\Support\Shelfy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Throwable;

class ReturnController extends Controller
{
    public function index(): View
    {
        try {
            $user = Auth::user();

            Loan::query()
                ->where('status', 'dipinjam')
                ->where('tanggal_jatuh_tempo', '<', date('Y-m-d'))
                ->get()
                ->each(fn ($loan) => $loan->update(['status' => 'terlambat']));

            $activeLoans = Loan::query()
                ->whereIn('status', ['dipinjam', 'terlambat'])
                ->orderBy('tanggal_jatuh_tempo')
                ->get();
            $returnedLoans = Loan::query()
                ->where('status', 'dikembalikan')
                ->orderBy('tanggal_kembali', 'desc')
                ->get();

            return view('shelfy.returns.index', [
                'returnLoans' => $user?->isLibrarian() ? $activeLoans : collect(),
                'returnedLoans' => Shelfy::filterLoansForUser($returnedLoans, $user),
                'canManageReturns' => $user?->isLibrarian(),
                'mongoError' => null,
            ]);
        } catch (Throwable $e) {
            return view('shelfy.returns.index', [
                'returnLoans' => collect(),
                'returnedLoans' => collect(),
                'canManageReturns' => false,
                'mongoError' => $e->getMessage(),
            ]);
        }
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isLibrarian(), 403, 'Pengembalian hanya bisa diproses pustakawan.');

        $validated = $request->validate([
            'id' => ['required', 'string'],
            'tanggal_kembali' => ['required', 'date'],
            'from' => ['nullable', 'string'],
        ]);

        $loan = Loan::query()->findOrFail($validated['id']);

        if ($loan->isReturned()) {
            return back()->with('danger', 'Peminjaman ini sudah dikembalikan.');
        }

        if ($loan->isWaitingPickup()) {
            return back()->with('danger', 'Buku belum dikonfirmasi diambil oleh pustakawan.');
        }

        $fine = Shelfy::lateFee($loan->tanggal_jatuh_tempo, $validated['tanggal_kembali']);

        $loan->update([
            'status' => 'dikembalikan',
            'tanggal_kembali' => $validated['tanggal_kembali'],
            'nomor_nota' => Shelfy::receiptNumber($loan),
            'petugas_pengembalian' => Auth::user()?->name ?? 'Admin SHELFY',
            'hari_terlambat' => $fine['hari_terlambat'],
            'denda_per_hari' => $fine['denda_per_hari'],
            'total_denda' => $fine['total_denda'],
            'payment_status' => $fine['total_denda'] > 0 ? 'belum_bayar' : 'lunas',
            'payment_method' => null,
            'payment_reference' => null,
            'paid_at' => null,
            'confirmed_at' => null,
            'confirmed_by' => null,
        ]);

        $book = Book::query()->find($loan->book_id);

        if ($book) {
            $nextAvailable = min((int) ($book->stok_total ?? 0), (int) ($book->stok_tersedia ?? 0) + 1);
            $book->update(['stok_tersedia' => $nextAvailable]);
        }

        return redirect()
            ->route('returns.receipt', Shelfy::id($loan))
            ->with('success', 'Pengembalian berhasil diproses.');
    }
}
