<?php

use App\Http\Controllers\SpreadSheetController;
use Illuminate\Support\Facades\Route;

Route::get('sps/{id}/{day}', [SpreadSheetController::class, 'test']);
