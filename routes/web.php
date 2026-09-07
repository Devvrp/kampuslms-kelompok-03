<?php

use App\Http\Controllers\CourseController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('dashboard');

Route::get('/tentang', function () {
    return view('tentang');
})->name('tentang');

Route::get('/courses', [CourseController::class, 'index'])
    ->name('courses.index');

Route::get('/courses/create', function () {
    return 'Halaman Create Mata Kuliah';
})->name('courses.create');

Route::get('/courses/{id}', [CourseController::class, 'show'])
    ->name('courses.show');