<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Loan;
use App\Support\Shelfy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Throwable;

class BookController extends Controller
{
    public function index(Request $request): View
    {
        try {
            $allBooks = Book::query()->orderBy('created_at', 'desc')->get();
            $isAdmin = $request->user()?->isAdmin();

            return view('shelfy.books.index', [
                'books' => $this->filterBooks($allBooks, $request),
                'categories' => $this->categories($allBooks),
                'editBook' => $isAdmin && $request->filled('edit') ? Book::query()->find($request->query('edit')) : null,
                'isAdmin' => $isAdmin,
                'mongoError' => null,
            ]);
        } catch (Throwable $e) {
            return view('shelfy.books.index', [
                'books' => collect(),
                'categories' => $this->categories(collect()),
                'editBook' => null,
                'isAdmin' => false,
                'mongoError' => $e->getMessage(),
            ]);
        }
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403, 'Halaman ini khusus bagian admin.');

        $validated = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'penulis' => ['required', 'string', 'max:255'],
            'kategori' => ['required', 'string', 'max:255'],
            'penerbit' => ['nullable', 'string', 'max:255'],
            'tahun' => ['nullable', 'integer', 'min:0'],
            'stok_total' => ['required', 'integer', 'min:0'],
            'isbn' => ['nullable', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
        ]);

        $payload = array_merge($validated, [
            'tahun' => (int) ($validated['tahun'] ?? 0),
            'stok_total' => (int) $validated['stok_total'],
        ]);

        if ($request->filled('id')) {
            $book = Book::query()->findOrFail($request->input('id'));
            $borrowed = max(0, (int) ($book->stok_total ?? 0) - (int) ($book->stok_tersedia ?? 0));
            $payload['stok_tersedia'] = max(0, (int) $payload['stok_total'] - $borrowed);
            $book->update($payload);

            return redirect()->route('books.index')->with('success', 'Data buku berhasil diperbarui.');
        }

        Book::query()->create(array_merge($payload, [
            'stok_tersedia' => (int) $payload['stok_total'],
            'dipinjam_count' => 0,
        ]));

        return redirect()->route('books.index')->with('success', 'Buku baru berhasil ditambahkan.');
    }

    public function destroy(string $id): RedirectResponse
    {
        abort_unless(request()->user()?->isAdmin(), 403, 'Halaman ini khusus bagian admin.');

        $activeLoans = Loan::query()
            ->where('book_id', $id)
            ->whereIn('status', ['dipinjam', 'terlambat'])
            ->count();

        if ($activeLoans > 0) {
            return back()->with('danger', 'Buku masih sedang dipinjam, tidak bisa dihapus.');
        }

        Book::query()->findOrFail($id)->delete();

        return back()->with('success', 'Data buku berhasil dihapus.');
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
