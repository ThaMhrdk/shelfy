<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Member extends Model
{
    protected $collection = 'members';

    protected $fillable = [
        'nama',
        'nim',
        'prodi',
        'email',
        'no_hp',
        'alamat',
        'status',
        'user_id',
    ];
}
