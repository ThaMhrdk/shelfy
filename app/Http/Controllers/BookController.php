<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Loan;
use App\Support\Shelfy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class BookController extends Controller
{
    public function index(Request $request): View
    {
        try {
            $allBooks = Book::query()->orderBy('created_at', 'desc')->get();
            $user = $request->user();

            return view('shelfy.books.index', [
                'books' => $this->filterBooks($allBooks, $request),
                'categories' => $this->categories($allBooks),
                'isAdmin' => $user?->isAdmin(),
                'canManageBooks' => $user?->isLibrarian(),
                'canDeleteBooks' => $user?->isAdmin(),
                'mongoError' => null,
            ]);
        } catch (Throwable $e) {
            return view('shelfy.books.index', [
                'books' => collect(),
                'categories' => $this->categories(collect()),
                'isAdmin' => false,
                'canManageBooks' => false,
                'canDeleteBooks' => false,
                'mongoError' => $e->getMessage(),
            ]);
        }
    }

    public function show(string $id): View
    {
        $book = Book::query()->findOrFail($id);
        $loans = Loan::query()
            ->where('book_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('shelfy.books.show', [
            'book' => $book,
            'loans' => $loans,
            'activeLoans' => $loans->whereIn('status', ['menunggu_diambil', 'dipinjam', 'terlambat'])->count(),
            'returnedLoans' => $loans->where('status', 'dikembalikan')->count(),
            'canManageLoans' => request()->user()?->isLibrarian(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isLibrarian(), 403, 'Kelola buku hanya untuk admin atau pustakawan.');

        $validated = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'penulis' => ['required', 'string', 'max:255'],
            'kategori' => ['required', 'string', 'max:255'],
            'penerbit' => ['nullable', 'string', 'max:255'],
            'tahun' => ['nullable', 'integer', 'min:0'],
            'stok_total' => ['required', 'integer', 'min:0'],
            'isbn' => ['nullable', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'cover' => ['nullable', 'image', 'max:2048'],
        ]);

        $payload = array_merge(collect($validated)->except('cover')->all(), [
            'tahun' => (int) ($validated['tahun'] ?? 0),
            'stok_total' => (int) $validated['stok_total'],
        ]);

        if ($request->filled('id')) {
            $book = Book::query()->findOrFail($request->input('id'));
            $borrowed = max(0, (int) ($book->stok_total ?? 0) - (int) ($book->stok_tersedia ?? 0));
            $payload['stok_tersedia'] = max(0, (int) $payload['stok_total'] - $borrowed);
            $payload['cover_path'] = $this->storeCover($request, $book->cover_path);
            $book->update($payload);

            return redirect()->route('books.index')->with('success', 'Data buku berhasil diperbarui.');
        }

        Book::query()->create(array_merge($payload, [
            'stok_tersedia' => (int) $payload['stok_total'],
            'dipinjam_count' => 0,
            'cover_path' => $this->storeCover($request),
        ]));

        return redirect()->route('books.index')->with('success', 'Buku baru berhasil ditambahkan.');
    }

    public function destroy(string $id): RedirectResponse
    {
        abort_unless(request()->user()?->isAdmin(), 403, 'Halaman ini khusus bagian admin.');

        $activeLoans = Loan::query()
            ->where('book_id', $id)
            ->whereIn('status', ['menunggu_diambil', 'dipinjam', 'terlambat'])
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

    private function storeCover(Request $request, ?string $current = null): ?string
    {
        if (! $request->hasFile('cover')) {
            return $current;
        }

        $file = $request->file('cover');
        $name = Str::slug(pathinfo((string) $file->getClientOriginalName(), PATHINFO_FILENAME));
        $filename = ($name ?: 'cover') . '-' . Str::random(8) . '.' . $file->getClientOriginalExtension();
        $directory = public_path('covers');

        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $file->move($directory, $filename);

        return 'covers/' . $filename;
    }
}
