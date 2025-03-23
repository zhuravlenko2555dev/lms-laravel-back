<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\AuthorController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\PublisherController;
use App\Http\Controllers\SubjectController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });
});

Route::resource('books', BookController::class);
Route::get('books-publish-years-range', [BookController::class, 'publishYearsRange']);
Route::resource('authors', AuthorController::class);
Route::resource('genres', GenreController::class);
Route::resource('publishers', PublisherController::class);
Route::resource('subjects', SubjectController::class);
Route::resource('media', MediaController::class)
    ->parameters(['media' => 'media']);
