<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\ApiLogoGeneratorController;

Route::post('/logo-save', [ApiLogoGeneratorController::class, 'save'])->name('logo.save');

//Route::get('/user', function (Request $request) {
//    return $request->user();
//})->middleware('auth:sanctum');

