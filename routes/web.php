<?php

use App\Http\Controllers\CourseController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
<<<<<<< HEAD
});
=======
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
>>>>>>> 3d94fecd1ee3edca41dc1162fae9d3fcfe614224
