<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use App\Support\ShelfySeeder;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('shelfy:fresh {--keep-technical : Jangan hapus collection teknis Laravel seperti cache/sessions/jobs}', function () {
    $database = DB::connection('mongodb')->getMongoDB();
    $collections = ['users', 'books', 'members', 'loans', 'cart_items'];
    $technicalCollections = ['cache', 'cache_locks', 'sessions', 'jobs', 'job_batches', 'failed_jobs', 'password_reset_tokens'];

    if (! $this->option('keep-technical')) {
        $collections = array_merge($collections, $technicalCollections);
    }

    foreach ($collections as $collection) {
        try {
            $database->selectCollection($collection)->drop();
            $this->line("Dropped: {$collection}");
        } catch (Throwable $e) {
            $this->warn("Skip {$collection}: {$e->getMessage()}");
        }
    }

    $this->info(ShelfySeeder::seedDemo());
    $this->info('SHELFY database fresh selesai.');
})->purpose('Bersihkan collection demo SHELFY di MongoDB lalu isi ulang data dummy.');
