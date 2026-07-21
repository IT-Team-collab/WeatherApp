<?php

use App\Http\Controllers\WeatherController;
use Illuminate\Support\Facades\Route;

// Route::inertia('/', 'Welcome')->name('home');
Route::redirect('/', '/weather');

// This route points to the weather controller, which will fetch data from the API.
Route::get('/weather', [WeatherController::class, 'show'])->name('weather');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
