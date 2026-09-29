<?php

use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
})->name('root');

Route::get('/tentang', function () {
    return view('tentang');
})->name('tentang');


/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    Route::get('/dashboard', function () {
        return view('welcome');
    })->name('dashboard');


    /*
    |--------------------------------------------------------------------------
    | Admin
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:admin')
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {

            Route::resource('users', UserController::class);

            Route::resource('courses', CourseController::class);
        });


    /*
    |--------------------------------------------------------------------------
    | Dosen
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:dosen')
        ->prefix('dosen')
        ->name('dosen.')
        ->group(function () {

            Route::scopeBindings()->group(function () {

                Route::resource(
                    'courses.materials',
                    MaterialController::class
                )->shallow();

                Route::resource(
                    'courses.assignments',
                    AssignmentController::class
                )->shallow();

                Route::resource(
                    'assignments.submissions',
                    SubmissionController::class
                )
                    ->only(['index', 'show'])
                    ->shallow();
            });
        });


    /*
    |--------------------------------------------------------------------------
    | Mahasiswa
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:mahasiswa')
        ->prefix('mahasiswa')
        ->name('mahasiswa.')
        ->group(function () {

            Route::scopeBindings()->group(function () {

                Route::resource(
                    'courses.materials',
                    MaterialController::class
                )
                    ->only(['index', 'show'])
                    ->shallow();

                Route::resource(
                    'courses.assignments',
                    AssignmentController::class
                )
                    ->only(['index', 'show'])
                    ->shallow();

                Route::resource(
                    'assignments.submissions',
                    SubmissionController::class
                )
                    ->only(['show', 'store'])
                    ->shallow();
            });
        });
});