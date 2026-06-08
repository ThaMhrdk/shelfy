<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use MongoDB\Laravel\Auth\User as Authenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const DEFAULT_ADMIN_EMAIL = 'admin@shelfy.test';
    public const DEFAULT_ADMIN_PASSWORD = 'admin123';

    protected $collection = 'users';

    protected $fillable = [
        'name',
        'nama',
        'email',
        'password',
        'role',
        'nim',
        'prodi',
        'no_hp',
        'alamat',
        'member_id',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function displayName(): string
    {
        return (string) ($this->name ?: $this->nama ?: 'Pengguna');
    }

    public function isAdmin(): bool
    {
        return in_array(($this->role ?? 'mahasiswa'), ['admin', 'kepala_pustakawan'], true);
    }

    public function isLibrarian(): bool
    {
        return $this->isAdmin() || ($this->role ?? 'mahasiswa') === 'pustakawan';
    }

    public function isHeadLibrarian(): bool
    {
        return $this->isAdmin();
    }

    public function isStaff(): bool
    {
        return $this->isAdmin() || ($this->role ?? 'mahasiswa') === 'pustakawan';
    }

    public function isStudent(): bool
    {
        return ($this->role ?? 'mahasiswa') === 'mahasiswa';
    }
}
