<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\Member;
use App\Support\Shelfy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Throwable;

class MemberController extends Controller
{
    public function index(Request $request): View
    {
        try {
            $members = Member::query()->orderBy('created_at', 'desc')->get();

            return view('shelfy.members.index', [
                'members' => $this->filterMembers($members, $request),
                'editMember' => $request->filled('edit') ? Member::query()->find($request->query('edit')) : null,
                'mongoError' => null,
            ]);
        } catch (Throwable $e) {
            return view('shelfy.members.index', [
                'members' => collect(),
                'editMember' => null,
                'mongoError' => $e->getMessage(),
            ]);
        }
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'nim' => ['required', 'string', 'max:100'],
            'prodi' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'no_hp' => ['nullable', 'string', 'max:100'],
            'alamat' => ['nullable', 'string'],
            'status' => ['nullable', 'in:aktif,nonaktif'],
        ]);

        $payload = array_merge($validated, [
            'status' => $validated['status'] ?? 'aktif',
        ]);

        if ($request->filled('id')) {
            Member::query()->findOrFail($request->input('id'))->update($payload);

            return redirect()->route('members.index')->with('success', 'Data anggota berhasil diperbarui.');
        }

        Member::query()->create($payload);

        return redirect()->route('members.index')->with('success', 'Anggota baru berhasil ditambahkan.');
    }

    public function destroy(string $id): RedirectResponse
    {
        $activeLoans = Loan::query()
            ->where('member_id', $id)
            ->whereIn('status', ['dipinjam', 'terlambat'])
            ->count();

        if ($activeLoans > 0) {
            return back()->with('danger', 'Anggota masih punya peminjaman aktif.');
        }

        Member::query()->findOrFail($id)->delete();

        return back()->with('success', 'Data anggota berhasil dihapus.');
    }

    private function filterMembers(Collection $members, Request $request): Collection
    {
        $q = (string) $request->query('q', '');
        $status = (string) $request->query('status', '');

        return $members
            ->filter(fn ($member) => Shelfy::containsText($member, ['nama', 'nim', 'prodi', 'email'], $q))
            ->filter(fn ($member) => $status === '' || (string) $member->status === $status)
            ->values();
    }
}
