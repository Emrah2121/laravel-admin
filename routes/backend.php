<?php

use App\Http\Controllers\Backend\AdminController;
use App\Http\Controllers\Backend\AdminLangController;
use App\Http\Controllers\Backend\AuthController;
use App\Http\Controllers\Backend\BlogController;
use App\Http\Controllers\Backend\CrudGeneratorController;
use App\Http\Controllers\Backend\DashboardController;
use App\Http\Controllers\Backend\DocumentsController;
use App\Http\Controllers\Backend\LanguageController;
use App\Http\Controllers\Backend\LanguageTranslationController;
use App\Http\Controllers\Backend\LogController;
use App\Http\Controllers\Backend\PermissionController;
use App\Http\Controllers\Backend\ProfileController;
use App\Http\Controllers\Backend\RoleController;
use App\Http\Controllers\Backend\SettingController;
use App\Http\Controllers\Backend\SmartSeederController;
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

    // crud-generator:start Service
    Route::resource('/services', \App\Http\Controllers\Backend\ServiceController::class);
    // crud-generator:end Service

    Route::resource('/admins', AdminController::class)->except('show');
    Route::resource('/roles', RoleController::class);
    Route::resource('/permissions', PermissionController::class);

    Route::post('/translations/import', [LanguageTranslationController::class, 'import'])->name('translations.import');
    Route::post('/translations/clear-cache', [LanguageTranslationController::class, 'clearCache'])->name('translations.clear_cache');
    Route::resource('/translations', LanguageTranslationController::class)->except('show');

    Route::resource('/logs', LogController::class)->only(['index', 'destroy']);

    Route::post('/documents/{document}/delete', [DocumentsController::class, 'deleteDocument'])->name('delete.document');
    Route::post('/documents/{document}/set-status', [DocumentsController::class, 'setStatus'])->name('setStatus.document');

    Route::middleware('crud-generator')->prefix('crud-generator')->name('crud_generator.')->group(function () {
        Route::get('/', [CrudGeneratorController::class, 'index'])->name('index');
        Route::get('/create', [CrudGeneratorController::class, 'create'])->name('create');
        Route::post('/preview', [CrudGeneratorController::class, 'preview'])->name('preview');
        Route::post('/', [CrudGeneratorController::class, 'store'])->name('store');
        Route::get('/{module}', [CrudGeneratorController::class, 'show'])->name('show')->where('module', '[A-Za-z0-9]+');
    });

    Route::middleware('crud-generator')->prefix('smart-seeder')->name('smart_seeder.')->group(function () {
        Route::get('/', [SmartSeederController::class, 'index'])->name('index');
        Route::put('/settings', [SmartSeederController::class, 'settings'])->name('settings');
        Route::post('/', [SmartSeederController::class, 'store'])->name('store');
        Route::get('/{seeder}', [SmartSeederController::class, 'show'])->name('show')->where('seeder', '[A-Za-z0-9]+');
        Route::post('/{seeder}/run', [SmartSeederController::class, 'run'])->name('run')->where('seeder', '[A-Za-z0-9]+');
        Route::get('/{seeder}/assets/{file}', [SmartSeederController::class, 'asset'])->name('asset')->where('seeder', '[A-Za-z0-9]+');
    });
});
