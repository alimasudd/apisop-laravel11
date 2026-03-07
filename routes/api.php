<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AreaController;
use App\Http\Controllers\Api\RuangController;
use App\Http\Controllers\Api\KategoriSopController;
use App\Http\Controllers\Api\SopController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // User CRUD
    Route::apiResource('users', UserController::class);

    // Area CRUD
    Route::apiResource('areas', AreaController::class);

    // Ruang CRUD
    Route::apiResource('ruangs', RuangController::class);

    // Kategori SOP CRUD
    Route::get('kategori-sops/{id}/sops', [KategoriSopController::class, 'sops']);
    Route::apiResource('kategori-sops', KategoriSopController::class);

    // SOP CRUD
    Route::apiResource('sops', SopController::class);
});
