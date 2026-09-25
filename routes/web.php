<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider, and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Lightweight health endpoint for Kubernetes probes (Laravel 10 has no
// built-in /up route). Kept above the SPA catch-all so it is not shadowed.
// Intentionally does NOT touch the database — liveness/readiness must not
// depend on downstreams. Deep dependency checks belong in a separate probe.
Route::get('up', fn () => response()->json(['status' => 'ok']));

Route::get('{any}', fn () => view('app'))->where(['any' => '.*']);
