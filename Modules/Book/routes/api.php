<?php

use Illuminate\Support\Facades\Route;
use Modules\Book\Http\Controllers\CategoryController;
use Modules\Book\Http\Controllers\AuthorController;
use Modules\Book\Http\Controllers\BookController;

// مسارات استعراض البيانات (عامة للجميع)
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);
Route::get('/authors', [AuthorController::class, 'index']);
Route::get('/authors/{id}', [AuthorController::class, 'show']);
Route::get('/books', [BookController::class, 'index']);
Route::get('/books/{id}', [BookController::class, 'show']);

// مسارات إدارة البيانات (محمية وتحتاج إلى تسجيل الدخول)
Route::middleware('auth:sanctum')->group(function () {
    // التصنيفات
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{id}', [CategoryController::class, 'update']);
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

    // المؤلفون
    Route::post('/authors', [AuthorController::class, 'store']);
    Route::put('/authors/{id}', [AuthorController::class, 'update']);
    Route::delete('/authors/{id}', [AuthorController::class, 'destroy']);

    // الكتب
    Route::post('/books', [BookController::class, 'store']);
    Route::put('/books/{id}', [BookController::class, 'update']); // تحديث الكتب بدون رفع ملفات جديدة (من نوع PUT)
    Route::post('/books/{id}', [BookController::class, 'update']); // تحديث الكتب مع رفع صورة الغلاف (من نوع POST مع _method=PUT)
    Route::delete('/books/{id}', [BookController::class, 'destroy']);
});
