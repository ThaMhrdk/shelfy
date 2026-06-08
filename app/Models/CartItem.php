<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class CartItem extends Model
{
    protected $collection = 'cart_items';

    protected $fillable = [
        'user_id',
        'member_id',
        'book_id',
        'judul_buku',
        'kategori_buku',
        'tanggal_pinjam',
        'tanggal_jatuh_tempo',
        'catatan',
    ];
}
