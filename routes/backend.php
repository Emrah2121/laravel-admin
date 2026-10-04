<?php

use App\Http\Controllers\Backend\AdminController;
use App\Http\Controllers\Backend\AdminLangController;
use App\Http\Controllers\Backend\AuthController;
use App\Http\Controllers\Backend\BlogController;
use App\Http\Controllers\Backend\DashboardController;
use App\Http\Controllers\Backend\DocumentsController;
use App\Http\Controllers\Backend\LanguageController;
use App\Http\Controllers\Backend\LanguageTranslationController;
use App\Http\Controllers\Backend\LogController;
use App\Http\Controllers\Backend\PermissionController;
use App\Http\Controllers\Backend\ProfileController;
use App\Http\Controllers\Backend\RoleController;
use App\Http\Controllers\Backend\SettingController;
use Illuminate\Support\Facades\Route;

Route::fallback(function () {
    return response()->view('backend.errors.404', [], 404);
});

Route::middleware(['guest:admin'])->group(function () {
    Route::get('/login-admin', [AuthController::class, 'loginForm'])->name('login.form');
    Route::post('/login-admin', [AuthController::class, 'login'])->name('login');
});

Route::patch('/lang', AdminLangController::class)->name('lang');

Route::group(['middleware' => ['auth:admin']], function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/profile', [ProfileController::class, 'profileForm'])->name('profile.form');
    Route::patch('/profile', [ProfileController::class, 'profile'])->name('profile');
    Route::patch('/profile/security', [ProfileController::class, 'security'])->name('profile.security');

    Route::resource('/languages', LanguageController::class)->except('show');
    Route::resource('/settings', SettingController::class);
    Route::resource('/blogs', BlogController::class);

    Route::resource('/admins', AdminController::class)->except('show');
    Route::resource('/roles', RoleController::class);
    Route::resource('/permissions', PermissionController::class);

    Route::post('/translations/import', [LanguageTranslationController::class, 'import'])->name('translations.import');
    Route::post('/translations/clear-cache', [LanguageTranslationController::class, 'clearCache'])->name('translations.clear_cache');
    Route::resource('/translations', LanguageTranslationController::class)->except('show');

    Route::resource('/logs', LogController::class)->only(['index', 'destroy']);

    Route::post('/documents/{document}/delete', [DocumentsController::class, 'deleteDocument'])->name('delete.document');
    Route::post('/documents/{document}/set-status', [DocumentsController::class, 'setStatus'])->name('setStatus.document');
});
