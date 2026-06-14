<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Loan;
use App\Support\Shelfy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
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
            'tanggal_kembali' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'from' => ['nullable', 'string'],
        ], [
            'tanggal_kembali.required' => 'Tanggal kembali wajib dipilih manual.',
            'tanggal_kembali.before_or_equal' => 'Tanggal kembali tidak boleh melewati hari ini.',
        ]);

        $loan = Loan::query()->findOrFail($validated['id']);

        if ($loan->isReturned()) {
            return back()->with('danger', 'Peminjaman ini sudah dikembalikan.');
        }

        if ($loan->isWaitingPickup()) {
            return back()->with('danger', 'Buku belum dikonfirmasi diambil oleh pustakawan.');
        }

        if ((string) ($loan->tanggal_pinjam ?? '') !== '' && $validated['tanggal_kembali'] < $loan->tanggal_pinjam) {
            throw ValidationException::withMessages([
                'tanggal_kembali' => 'Tanggal kembali tidak boleh lebih awal dari tanggal pinjam.',
            ]);
        }

        $fine = Shelfy::lateFee($loan->tanggal_jatuh_tempo, $validated['tanggal_kembali']);

        $loan->update([
            'status' => 'dikembalikan',
            'tanggal_kembali' => $validated['tanggal_kembali'],
            'nomor_nota' => Shelfy::receiptNumber($loan),
            'petugas_pengembalian' => Auth::user()?->displayName() ?? 'Pustakawan SHELFY',
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

    public function extend(Request $request, string $id): RedirectResponse
    {
        abort_unless($request->user()?->isLibrarian(), 403, 'Perpanjangan hanya bisa diproses pustakawan.');

        $validated = $request->validate([
            'tanggal_jatuh_tempo_baru' => ['required', 'date_format:Y-m-d', 'after:today'],
            'catatan_perpanjangan' => ['required', 'string', 'max:500'],
            'dialog' => ['nullable', 'string'],
        ], [
            'tanggal_jatuh_tempo_baru.required' => 'Tanggal jatuh tempo baru wajib dipilih.',
            'tanggal_jatuh_tempo_baru.after' => 'Tanggal jatuh tempo baru harus setelah hari ini.',
            'catatan_perpanjangan.required' => 'Alasan perpanjangan wajib diisi.',
        ]);

        $loan = Loan::query()->findOrFail($id);

        if (($loan->status ?? '') !== 'dipinjam') {
            return back()->with('danger', 'Perpanjangan hanya tersedia untuk buku aktif yang belum terlambat.');
        }

        $oldDueDate = Carbon::parse($loan->tanggal_jatuh_tempo)->startOfDay();
        $newDueDate = Carbon::parse($validated['tanggal_jatuh_tempo_baru'])->startOfDay();

        if ($newDueDate->lessThanOrEqualTo($oldDueDate)) {
            throw ValidationException::withMessages([
                'tanggal_jatuh_tempo_baru' => 'Tanggal baru harus melewati jatuh tempo saat ini.',
            ]);
        }

        $history = is_array($loan->riwayat_perpanjangan ?? null)
            ? $loan->riwayat_perpanjangan
            : [];
        $history[] = [
            'tanggal_lama' => $loan->tanggal_jatuh_tempo,
            'tanggal_baru' => $newDueDate->format('Y-m-d'),
            'diproses_pada' => now()->format('Y-m-d H:i:s'),
            'diproses_oleh' => $request->user()?->displayName() ?? 'Pustakawan SHELFY',
            'catatan' => $validated['catatan_perpanjangan'],
        ];

        $loan->update([
            'tanggal_jatuh_tempo' => $newDueDate->format('Y-m-d'),
            'status' => 'dipinjam',
            'tanggal_perpanjangan_terakhir' => now()->format('Y-m-d H:i:s'),
            'petugas_perpanjangan' => $request->user()?->displayName() ?? 'Pustakawan SHELFY',
            'catatan_perpanjangan' => $validated['catatan_perpanjangan'],
            'riwayat_perpanjangan' => $history,
        ]);

        return back()->with('success', 'Peminjaman berhasil diperpanjang sampai '.$newDueDate->format('Y-m-d').'.');
    }
}
