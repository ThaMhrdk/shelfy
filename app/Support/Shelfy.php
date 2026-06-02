<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class Shelfy
{
    public const FINE_PER_DAY = 2000;

    private static ?array $mongoStatus = null;

    public static function moneyless(int|float|null $value): string
    {
        return number_format((float) ($value ?? 0), 0, ',', '.');
    }

    public static function rupiah(int|float|null $value): string
    {
        return 'Rp ' . self::moneyless($value);
    }

    public static function statusLabel(?string $status): string
    {
        return match ((string) $status) {
            'tersedia' => 'Tersedia',
            'habis' => 'Habis',
            'aktif' => 'Aktif',
            'nonaktif' => 'Nonaktif',
            'dipinjam' => 'Dipinjam',
            'dikembalikan' => 'Dikembalikan',
            'terlambat' => 'Terlambat',
            'admin' => 'Admin',
            'mahasiswa' => 'Mahasiswa',
            'belum_bayar' => 'Belum Bayar',
            'menunggu_konfirmasi' => 'Menunggu Konfirmasi',
            'lunas' => 'Lunas',
            default => Str::headline((string) $status),
        };
    }

    public static function statusClass(?string $status): string
    {
        return match ((string) $status) {
            'tersedia', 'aktif', 'dikembalikan', 'lunas' => 'success',
            'dipinjam', 'menunggu_konfirmasi' => 'warning',
            'terlambat', 'habis', 'nonaktif', 'belum_bayar' => 'danger',
            default => 'neutral',
        };
    }

    public static function initials(?object $user): string
    {
        $name = trim((string) ($user?->name ?? $user?->nama ?? 'S'));
        $words = preg_split('/\s+/', $name) ?: [];
        $first = strtoupper(substr((string) ($words[0] ?? 'S'), 0, 1));
        $second = strtoupper(substr((string) ($words[1] ?? ''), 0, 1));

        return $first . ($second !== '' ? $second : '');
    }

    public static function id(object|string|null $model): string
    {
        if (is_string($model) || $model === null) {
            return (string) $model;
        }

        return (string) (method_exists($model, 'getKey') ? $model->getKey() : ($model->_id ?? $model->id ?? ''));
    }

    public static function lateFee(?string $dueDate, ?string $returnDate = null): array
    {
        try {
            $due = new \DateTimeImmutable($dueDate ?: date('Y-m-d'));
            $returned = new \DateTimeImmutable($returnDate ?: date('Y-m-d'));
            $lateDays = max(0, (int) $due->diff($returned)->format('%r%a'));
        } catch (Throwable) {
            $lateDays = 0;
        }

        return [
            'hari_terlambat' => $lateDays,
            'denda_per_hari' => self::FINE_PER_DAY,
            'total_denda' => $lateDays * self::FINE_PER_DAY,
        ];
    }

    public static function paymentMethodOptions(): array
    {
        return [
            'transfer' => 'Transfer',
            'qris' => 'QRIS',
        ];
    }

    public static function paymentMethodLabel(?string $method): string
    {
        $method = (string) $method;

        return self::paymentMethodOptions()[$method] ?? '-';
    }

    public static function paymentStatus(object $loan, ?array $fine = null): string
    {
        $status = (string) ($loan->payment_status ?? '');

        if (in_array($status, ['belum_bayar', 'menunggu_konfirmasi', 'lunas'], true)) {
            return $status;
        }

        $fine ??= [
            'total_denda' => (int) ($loan->total_denda ?? 0),
        ];

        return ((int) ($fine['total_denda'] ?? 0)) > 0 ? 'belum_bayar' : 'lunas';
    }

    public static function receiptNumber(object|string|null $loan): string
    {
        if (is_object($loan) && (string) ($loan->nomor_nota ?? '') !== '') {
            return (string) $loan->nomor_nota;
        }

        return 'SHELFY-' . date('Ymd') . '-' . strtoupper(substr(self::id($loan), -6));
    }

    public static function mongoStatus(): array
    {
        if (self::$mongoStatus !== null) {
            return self::$mongoStatus;
        }

        try {
            DB::connection('mongodb')->ping();

            return self::$mongoStatus = [
                'connected' => true,
                'error' => null,
                'database' => (string) config('database.connections.mongodb.database', 'shelfy_db'),
            ];
        } catch (Throwable $e) {
            return self::$mongoStatus = [
                'connected' => false,
                'error' => $e->getMessage(),
                'database' => (string) config('database.connections.mongodb.database', 'shelfy_db'),
            ];
        }
    }

    public static function containsText(object $row, array $fields, string $term): bool
    {
        $term = trim(Str::lower($term));

        if ($term === '') {
            return true;
        }

        foreach ($fields as $field) {
            if (Str::contains(Str::lower((string) ($row->{$field} ?? '')), $term)) {
                return true;
            }
        }

        return false;
    }

    public static function topBorrowed(Collection $books, int $limit = 5): Collection
    {
        return $books
            ->sortByDesc(fn ($book) => (int) ($book->dipinjam_count ?? 0))
            ->take($limit)
            ->values();
    }

    public static function loanBelongsToUser(object $loan, ?object $user): bool
    {
        if ($user?->isAdmin()) {
            return true;
        }

        $memberId = (string) ($user?->member_id ?? '');
        $nim = (string) ($user?->nim ?? '');

        return ($memberId !== '' && (string) ($loan->member_id ?? '') === $memberId)
            || ($nim !== '' && (string) ($loan->nim_anggota ?? '') === $nim);
    }

    public static function filterLoansForUser(Collection $loans, ?object $user): Collection
    {
        if ($user?->isAdmin()) {
            return $loans->values();
        }

        return $loans
            ->filter(fn ($loan) => self::loanBelongsToUser($loan, $user))
            ->values();
    }

    public static function homeRouteName(?object $user): string
    {
        return $user?->isAdmin() ? 'dashboard' : 'student.dashboard';
    }
}
