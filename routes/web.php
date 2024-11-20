<?php
use App\Http\Controllers\LogoController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LogoController::class, 'showForm']);


