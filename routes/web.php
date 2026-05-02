<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\InstallerController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

/*
|--------------------------------------------------------------------------
| One-Time Web Installer Routes
|--------------------------------------------------------------------------
| These routes are intentionally outside any middleware group.
| Security is enforced by the token + installed.lock guard inside the controller.
|
*/
Route::get('/installer/{token}', [InstallerController::class, 'showForm'])
    ->name('installer.show')
    ->where('token', '[a-f0-9]{64}'); // only valid hex tokens

Route::post('/installer/{token}', [InstallerController::class, 'runInstall'])
    ->name('installer.run')
    ->where('token', '[a-f0-9]{64}');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [CustomerController::class, 'dashboard'])->name('dashboard');
    
    Route::resource('customers', CustomerController::class);

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
