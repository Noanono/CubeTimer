<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DuelController;
use App\Http\Controllers\Api\ScrambleController;
use App\Http\Controllers\Api\SolveController;
use App\Http\Controllers\Api\StatisticsController;
use App\Http\Controllers\Api\TimerController;
use Illuminate\Support\Facades\Route;

// Public auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Scramble
    Route::get('/scramble', [ScrambleController::class, 'generate']);

    // Timer / Solves
    Route::post('/solves', [TimerController::class, 'store']);
    Route::delete('/solves/{id}', [SolveController::class, 'destroy']);
    Route::patch('/solves/{id}/dnf', [SolveController::class, 'toggleDnf']);

    // Statistics
    Route::get('/statistics', [StatisticsController::class, 'index']);

    // Duel
    Route::post('/duel/create', [DuelController::class, 'createRoom']);
    Route::post('/duel/join', [DuelController::class, 'joinRoom']);
    Route::get('/duel/{code}', [DuelController::class, 'showRoom']);
    Route::post('/duel/{code}/submit', [DuelController::class, 'submitTime']);
});
