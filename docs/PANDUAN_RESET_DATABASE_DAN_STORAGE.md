# Panduan Reset Database dan Storage SHELFY

## Reset MongoDB untuk demo

Gunakan command khusus ini dari folder project:

```bash
php artisan shelfy:fresh
```

Command tersebut akan menghapus collection demo SHELFY:

- `users`
- `books`
- `members`
- `loans`
- `cart_items`

Command juga menghapus collection teknis Laravel yang tidak perlu untuk demo jika masih ada dari konfigurasi lama:

- `cache`
- `cache_locks`
- `sessions`
- `jobs`
- `job_batches`
- `failed_jobs`
- `password_reset_tokens`

Setelah bersih, data dummy langsung diisi ulang lewat seeder.

Kalau ingin mempertahankan collection teknis Laravel:

```bash
php artisan shelfy:fresh --keep-technical
```

## Akun dummy awal

Semua email dummy memakai `@gmail.com`.

| Role | Email | Password |
| --- | --- | --- |
| Admin | `admin@gmail.com` | `admin123` |
| Pustakawan | `pustakawan@gmail.com` | `pustaka123` |
| Mahasiswa | `michael@gmail.com` | `mahasiswa123` |
| Mahasiswa | `mumpuni@gmail.com` | `mahasiswa123` |
| Mahasiswa | `fadhil@gmail.com` | `mahasiswa123` |
| Mahasiswa | `anantha@gmail.com` | `mahasiswa123` |

Role yang dipakai hanya:

- `admin`
- `pustakawan`
- `mahasiswa`

## Storage untuk cover buku dan foto profil

Jalankan sekali:

```bash
php artisan storage:link
```

Laravel akan membuat link:

```text
public/storage -> storage/app/public
```

File upload disimpan di:

- cover buku: `storage/app/public/covers`
- foto profil admin/pustakawan: `storage/app/public/profile-photos`

Browser mengaksesnya lewat URL:

```text
/storage/covers/nama-file.jpg
/storage/profile-photos/nama-file.jpg
```

Ini lebih rapi untuk presentasi karena MongoDB hanya menyimpan path file, sedangkan file gambar disimpan di storage Laravel.
