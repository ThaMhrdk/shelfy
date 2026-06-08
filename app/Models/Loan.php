<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Loan extends Model
{
    protected $collection = 'loans';

    protected $fillable = [
        'book_id',
        'member_id',
        'judul_buku',
        'kategori_buku',
        'nama_anggota',
        'nim_anggota',
        'tanggal_pinjam',
        'tanggal_jatuh_tempo',
        'tanggal_kembali',
        'status',
        'catatan',
        'dibuat_oleh',
        'petugas_pengembalian',
        'hari_terlambat',
        'denda_per_hari',
        'total_denda',
        'nomor_nota',
        'payment_status',
        'payment_method',
        'payment_reference',
        'paid_at',
        'confirmed_at',
        'confirmed_by',
        'tanggal_diambil',
        'bukti_pengambilan',
        'petugas_pengambilan',
    ];

    protected function casts(): array
    {
        return [
            'hari_terlambat' => 'integer',
            'denda_per_hari' => 'integer',
            'total_denda' => 'integer',
        ];
    }

    public function isReturned(): bool
    {
        return ($this->status ?? '') === 'dikembalikan';
    }

    public function isWaitingPickup(): bool
    {
        return ($this->status ?? '') === 'menunggu_diambil';
    }
}
