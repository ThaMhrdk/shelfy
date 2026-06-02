<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Loan;
use App\Support\Shelfy;
use App\Support\ShelfySeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Throwable;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        try {
            $user = $request->user();
            $books = $this->filteredBooks($request);
            $allBooks = Book::query()->orderBy('judul')->get();
            $allLoans = Loan::query()->orderBy('created_at', 'desc')->get();
            $loans = Shelfy::filterLoansForUser($allLoans, $user);

            return view('dashboard', [
                'stats' => $this->stats($allBooks, $loans),
                'books' => $books->take(6),
                'loans' => $loans->take(5),
                'categories' => $this->categories($allBooks),
                'topBooks' => $user?->isAdmin() ? Shelfy::topBorrowed($allBooks) : collect(),
                'categoryLoans' => $user?->isAdmin() ? $this->categoryLoans($allLoans) : collect(),
                'editBook' => null,
                'isAdmin' => $user?->isAdmin(),
                'mongoError' => null,
            ]);
        } catch (Throwable $e) {
            return view('dashboard', [
                'stats' => $this->stats(collect(), collect()),
                'books' => collect(),
                'loans' => collect(),
                'categories' => $this->categories(collect()),
                'topBooks' => collect(),
                'categoryLoans' => collect(),
                'editBook' => null,
                'isAdmin' => false,
                'mongoError' => $e->getMessage(),
            ]);
        }
    }

    public function seed(): RedirectResponse
    {
        try {
            return back()->with('success', ShelfySeeder::seedDemo());
        } catch (Throwable $e) {
            return back()->with('danger', 'Seed gagal: ' . $e->getMessage());
        }
    }

    private function filteredBooks(Request $request): Collection
    {
        $books = Book::query()->orderBy('created_at', 'desc')->get();
        $q = (string) $request->query('q', '');

        return $books
            ->filter(fn ($book) => Shelfy::containsText($book, ['judul', 'penulis', 'isbn'], $q))
            ->values();
    }

    private function stats(Collection $books, Collection $loans): array
    {
        return [
            'judul' => $books->count(),
            'stok_total' => $books->sum(fn ($book) => (int) ($book->stok_total ?? 0)),
            'stok_tersedia' => $books->sum(fn ($book) => (int) ($book->stok_tersedia ?? 0)),
            'avg_stok' => $books->count() > 0 ? $books->avg(fn ($book) => (int) ($book->stok_total ?? 0)) : 0,
            'gt_five' => $books->filter(fn ($book) => (int) ($book->stok_total ?? 0) > 5)->count(),
            'dipinjam' => $loans->whereIn('status', ['dipinjam', 'terlambat'])->count(),
            'terlambat' => $loans->where('status', 'terlambat')->count(),
        ];
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
