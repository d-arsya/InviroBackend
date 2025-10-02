<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\SpreadSheetController;
use Illuminate\Support\Facades\Route;

Route::get('sps/{id}/{day}', [SpreadSheetController::class, 'test']);
Route::post('login', [AuthController::class, 'login']);
Route::middleware('auth:api')->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
});
