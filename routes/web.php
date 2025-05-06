<?php

use Illuminate\Support\Facades\Route;
use JustBetter\ExactClient\Http\Controllers\AuthController;

Route::get('authorize/{connection}', [AuthController::class, 'redirect'])->name('exact.auth.redirect');
Route::get('callback/{connection}', [AuthController::class, 'callback'])->name('exact.auth.callback');
