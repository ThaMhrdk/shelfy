<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Support\Shelfy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReceiptController extends Controller
{
    public function show(Request $request, string $id): View
    {
        $loan = Loan::query()->find($id);

        if (! $loan) {
            abort(404, 'Data nota tidak ditemukan.');
        }

        if (! $loan->isReturned()) {
            abort(403, 'Nota hanya tersedia setelah pengembalian diproses.');
        }

        if (! Shelfy::loanBelongsToUser($loan, $request->user())) {
            abort(403, 'Nota ini bukan milik akun kamu.');
        }

        return view('shelfy.receipts.show', [
            'receipt' => $loan,
            'receiptNumber' => Shelfy::receiptNumber($loan),
        ]);
    }

    public function pay(Request $request, string $id): RedirectResponse
    {
        abort_unless(! $request->user()?->isAdmin(), 403, 'Pembayaran denda khusus bagian mahasiswa.');

        $validated = $request->validate([
            'payment_method' => ['required', 'string', 'in:' . implode(',', array_keys(Shelfy::paymentMethodOptions()))],
        ]);

        $loan = $this->returnedLoanOrFail($id);

        if (! Shelfy::loanBelongsToUser($loan, $request->user())) {
            abort(403, 'Pembayaran ini bukan milik akun kamu.');
        }

        $fine = $this->fine($loan);
        $status = Shelfy::paymentStatus($loan, $fine);

        if ((int) $fine['total_denda'] <= 0) {
            return back()->with('danger', 'Tidak ada denda yang perlu dibayar.');
        }

        if ($status === 'lunas') {
            return back()->with('danger', 'Denda peminjaman ini sudah lunas.');
        }

        $loan->update([
            'payment_status' => 'menunggu_konfirmasi',
            'payment_method' => $validated['payment_method'],
            'payment_reference' => 'SHELFY-PAY-' . date('YmdHis') . '-' . strtoupper(substr(Shelfy::id($loan), -4)),
            'paid_at' => date('Y-m-d H:i:s'),
            'confirmed_at' => null,
            'confirmed_by' => null,
        ]);

        return redirect()
            ->route('returns.receipt', Shelfy::id($loan))
            ->with('success', 'Pembayaran via ' . Shelfy::paymentMethodLabel($validated['payment_method']) . ' sudah dicatat. Tunggu konfirmasi admin.');
    }

    public function confirm(Request $request, string $id): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403, 'Konfirmasi pembayaran khusus bagian admin.');

        $loan = $this->returnedLoanOrFail($id);
        $fine = $this->fine($loan);
        $status = Shelfy::paymentStatus($loan, $fine);

        if ((int) $fine['total_denda'] <= 0) {
            return back()->with('danger', 'Peminjaman ini tidak memiliki denda.');
        }

        if ($status === 'belum_bayar') {
            return back()->with('danger', 'Mahasiswa belum memilih metode pembayaran.');
        }

        if ($status === 'lunas') {
            return back()->with('danger', 'Pembayaran denda sudah dikonfirmasi.');
        }

        $loan->update([
            'payment_status' => 'lunas',
            'confirmed_at' => date('Y-m-d H:i:s'),
            'confirmed_by' => $request->user()?->name ?? 'Admin SHELFY',
        ]);

        return redirect()
            ->route('returns.receipt', Shelfy::id($loan))
            ->with('success', 'Pembayaran denda sudah dikonfirmasi lunas.');
    }

    private function returnedLoanOrFail(string $id): Loan
    {
        $loan = Loan::query()->find($id);

        if (! $loan) {
            abort(404, 'Data nota tidak ditemukan.');
        }

        if (! $loan->isReturned()) {
            abort(403, 'Nota hanya tersedia setelah pengembalian diproses.');
        }

        return $loan;
    }

    private function fine(Loan $loan): array
    {
        return [
            'hari_terlambat' => (int) ($loan->hari_terlambat ?? 0),
            'denda_per_hari' => (int) ($loan->denda_per_hari ?? Shelfy::FINE_PER_DAY),
            'total_denda' => (int) ($loan->total_denda ?? 0),
        ];
    }
}
