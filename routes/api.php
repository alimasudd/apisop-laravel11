<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\Admin\AreaController;
use App\Http\Controllers\Api\Admin\RuangController;
use App\Http\Controllers\Api\Admin\KategoriSopController;
use App\Http\Controllers\Api\Admin\SopController;
use App\Http\Controllers\Api\Admin\SopLangkahController;
use App\Http\Controllers\Api\Admin\SopTugasController;
use App\Http\Controllers\Api\Admin\SopPelaksanaController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // --- API ADMIN ---
    Route::group(['prefix' => 'admin', 'as' => 'admin.'], function () {
        Route::apiResource('users', UserController::class);
        Route::apiResource('areas', AreaController::class);
        Route::apiResource('ruangs', RuangController::class);
        Route::get('kategori-sops/{id}/sops', [KategoriSopController::class, 'sops']);
        Route::apiResource('kategori-sops', KategoriSopController::class);
        Route::apiResource('sops', SopController::class);
        Route::apiResource('langkah-sops', SopLangkahController::class);
        Route::apiResource('tugas-sops', SopTugasController::class)->only(['index', 'store', 'destroy']);
        Route::apiResource('pelaksanaan-sops', SopPelaksanaController::class);
    });

    // --- API KARYAWAN ---
    Route::group(['prefix' => 'karyawan'], function () {
        Route::get('/dashboard', [\App\Http\Controllers\Api\Karyawan\DashboardController::class, 'index']);

        // Tugas / Pekerjaan
        Route::get('/tugas', [\App\Http\Controllers\Api\Karyawan\TugasController::class, 'index']);
        Route::post('/tugas/{langkah_id}/mulai', [\App\Http\Controllers\Api\Karyawan\TugasController::class, 'mulai']);
        Route::post('/tugas/{langkah_id}/selesai', [\App\Http\Controllers\Api\Karyawan\TugasController::class, 'selesai']);

        // Laporan Saya
        Route::get('/laporan', [\App\Http\Controllers\Api\Karyawan\LaporanController::class, 'index']);

        // Akun / Profil
        Route::get('/profile', [\App\Http\Controllers\Api\Karyawan\AccountController::class, 'profile']);
        Route::put('/profile', [\App\Http\Controllers\Api\Karyawan\AccountController::class, 'updateProfile']);
        Route::post('/ganti-password', [\App\Http\Controllers\Api\Karyawan\AccountController::class, 'changePassword']);
    });

    // --- BASE ROUTES (Tetap dipertahankan agar tidak merusak fungsi lama) ---
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
    // Langkah SOP CRUD
    Route::apiResource('langkah-sops', SopLangkahController::class);
    // Tugas SOP CRUD
    Route::apiResource('tugas-sops', SopTugasController::class)->only(['index', 'store', 'destroy']);
    // Pelaksanaan SOP CRUD
    Route::apiResource('pelaksanaan-sops', SopPelaksanaController::class);
});

Route::get('/test', function () {
    return response()->json([
        'status' => 'API hidup'
    ]);
});
