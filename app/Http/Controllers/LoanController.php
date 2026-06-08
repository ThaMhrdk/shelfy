<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Support\Shelfy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
                'canManageLoans' => $user?->isLibrarian(),
                'mongoError' => null,
            ]);
        } catch (Throwable $e) {
            return view('shelfy.loans.index', [
                'loans' => collect(),
                'canManageLoans' => false,
                'mongoError' => $e->getMessage(),
            ]);
        }
    }

    public function pickup(Request $request, string $id): RedirectResponse
    {
        abort_unless($request->user()?->isLibrarian(), 403, 'Bukti pengambilan hanya bisa diproses pustakawan.');

        $validated = $request->validate([
            'bukti_pengambilan' => ['nullable', 'string', 'max:255'],
        ]);

        $loan = Loan::query()->findOrFail($id);

        if (! $loan->isWaitingPickup()) {
            return back()->with('danger', 'Buku ini tidak sedang menunggu pengambilan.');
        }

        $loan->update([
            'status' => $loan->tanggal_jatuh_tempo < date('Y-m-d') ? 'terlambat' : 'dipinjam',
            'tanggal_diambil' => date('Y-m-d H:i:s'),
            'bukti_pengambilan' => $validated['bukti_pengambilan'] ?: 'Buku diambil langsung oleh anggota.',
            'petugas_pengambilan' => $request->user()?->displayName() ?? 'Pustakawan SHELFY',
        ]);

        return back()->with('success', 'Bukti buku sudah diambil berhasil dicatat.');
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
