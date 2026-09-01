<?php

use Illuminate\Support\Facades\Route;
use Modules\Review\Http\Controllers\ReviewController;

// مسار استعراض التقييمات لكتاب محدد (عام للجميع)
Route::get('/books/{book_id}/reviews', [ReviewController::class, 'index']);

// مسار إضافة التقييم (محمي ويحتاج لتسجيل الدخول)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/books/{book_id}/reviews', [ReviewController::class, 'store']);
});
