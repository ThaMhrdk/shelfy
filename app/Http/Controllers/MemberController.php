<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\Member;
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
                'activeLoanCounts' => $this->activeLoanCounts(),
                'mongoError' => null,
            ]);
        } catch (Throwable $e) {
            return view('shelfy.members.index', [
                'members' => collect(),
                'activeLoanCounts' => collect(),
                'mongoError' => $e->getMessage(),
            ]);
        }
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

    private function activeLoanCounts(): Collection
    {
        return Loan::query()
            ->whereIn('status', ['menunggu_diambil', 'dipinjam', 'terlambat'])
            ->get()
            ->groupBy(fn ($loan) => (string) ($loan->member_id ?? ''))
            ->map(fn ($rows) => $rows->count());
    }
}
