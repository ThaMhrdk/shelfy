<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Loan;
use App\Support\Shelfy;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Throwable;

class RecapController extends Controller
{
    public function index(): View
    {
        try {
            $books = Book::query()->orderBy('judul')->get();
            $loans = Loan::query()->orderBy('created_at', 'desc')->get();

            return view('shelfy.recap.index', [
                'stats' => $this->stats($books, $loans),
                'topBooks' => Shelfy::topBorrowed($books),
                'categoryLoans' => $this->categoryLoans($loans),
                'mongoError' => null,
            ]);
        } catch (Throwable $e) {
            return view('shelfy.recap.index', [
                'stats' => $this->stats(collect(), collect()),
                'topBooks' => collect(),
                'categoryLoans' => collect(),
                'mongoError' => $e->getMessage(),
            ]);
        }
    }

    private function stats(Collection $books, Collection $loans): array
    {
        return [
            'stok_total' => $books->sum(fn ($book) => (int) ($book->stok_total ?? 0)),
            'avg_stok' => $books->count() > 0 ? $books->avg(fn ($book) => (int) ($book->stok_total ?? 0)) : 0,
            'gt_five' => $books->filter(fn ($book) => (int) ($book->stok_total ?? 0) > 5)->count(),
            'terlambat' => $loans->where('status', 'terlambat')->count(),
        ];
    }

    private function categoryLoans(Collection $loans): Collection
    {
        return $loans
            ->groupBy(fn ($loan) => $loan->kategori_buku ?: 'Tanpa Kategori')
            ->map(fn ($rows, $category) => (object) ['kategori' => $category, 'total' => $rows->count()])
            ->sortByDesc('total')
            ->take(5)
            ->values();
    }
}
