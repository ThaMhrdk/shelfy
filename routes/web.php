<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecapController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\ReturnController;
use App\Http\Controllers\StudentController;
use App\Support\Shelfy;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route(Shelfy::homeRouteName(auth()->user()))
        : redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/returns/{id}/nota', [ReceiptController::class, 'show'])->name('returns.receipt');
    Route::post('/returns/{id}/nota/pay', [ReceiptController::class, 'pay'])->name('returns.receipt.pay');
    Route::post('/returns/{id}/nota/confirm', [ReceiptController::class, 'confirm'])->name('returns.receipt.confirm');

    Route::middleware('admin')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::post('/seed-demo', [DashboardController::class, 'seed'])->name('shelfy.seed');

        Route::get('/books', [BookController::class, 'index'])->name('books.index');
        Route::post('/books', [BookController::class, 'store'])->name('books.store');
        Route::delete('/books/{id}', [BookController::class, 'destroy'])->name('books.destroy');

        Route::get('/members', [MemberController::class, 'index'])->name('members.index');
        Route::post('/members', [MemberController::class, 'store'])->name('members.store');
        Route::delete('/members/{id}', [MemberController::class, 'destroy'])->name('members.destroy');

        Route::get('/loans', [LoanController::class, 'index'])->name('loans.index');
        Route::post('/loans', [LoanController::class, 'store'])->name('loans.store');

        Route::get('/returns', [ReturnController::class, 'index'])->name('returns.index');
        Route::post('/returns', [ReturnController::class, 'store'])->name('returns.store');

        Route::get('/recap', [RecapController::class, 'index'])->name('recap.index');
    });

    Route::middleware('student')->prefix('student')->name('student.')->group(function () {
        Route::get('/dashboard', [StudentController::class, 'dashboard'])->name('dashboard');
        Route::get('/books', [StudentController::class, 'books'])->name('books');
        Route::get('/loans', [StudentController::class, 'loans'])->name('loans');
        Route::post('/loans', [StudentController::class, 'storeLoan'])->name('loans.store');
        Route::get('/history', [StudentController::class, 'history'])->name('history');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
