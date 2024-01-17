<?php

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

Route::namespace('App\Http\Controllers\Api')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('login', 'AuthController@login');

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', 'AuthController@logout');
            Route::get('me', 'AuthController@me');
        });
    });
});
