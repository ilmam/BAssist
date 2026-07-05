<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoryController;

Route::get('/', function () {
    return redirect()->route('categories.index');
});

Route::view('theme-test', 'pages.theme-test')->name('theme.test');

Route::get('categories/modal/{id}/view', [CategoryController::class, 'modalView'])->name('categories.modalview');
Route::get('categories/modal/{id}/edit', [CategoryController::class, 'modalEdit'])->name('categories.modaledit');
Route::get('categories/modal/{id}/delete', [CategoryController::class, 'modalDelete'])->name('categories.modaldelete');
Route::get('categories/modal/{id}', [CategoryController::class, 'modalShow'])->name('categories.modalshow');

Route::resource('categories', CategoryController::class);
