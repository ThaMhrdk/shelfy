<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Book extends Model
{
    protected $collection = 'books';

    protected $fillable = [
        'judul',
        'penulis',
        'kategori',
        'penerbit',
        'tahun',
        'isbn',
        'stok_total',
        'stok_tersedia',
        'dipinjam_count',
        'deskripsi',
        'cover_path',
    ];

    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'stok_total' => 'integer',
            'stok_tersedia' => 'integer',
            'dipinjam_count' => 'integer',
        ];
    }

    public function status(): string
    {
        return ((int) ($this->stok_tersedia ?? 0)) > 0 ? 'tersedia' : 'habis';
    }
}
