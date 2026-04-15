<?php

use App\Livewire\DuelLobby;
use App\Livewire\DuelRoom;
use App\Livewire\Statistics;
use App\Livewire\Timer;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::get('timer', Timer::class)->name('timer');
    Route::get('statistics', Statistics::class)->name('statistics');
    Route::get('duel', DuelLobby::class)->name('duel.lobby');
    Route::get('duel/{code}', DuelRoom::class)->name('duel.room');
});

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
