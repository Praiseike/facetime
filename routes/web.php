<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserAuthController;
use App\Http\Controllers\RoomController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/


Route::get('/login',[UserAuthController::class,'login'])->name('auth-login');
Route::get('/register',[UserAuthController::class,'register'])->name('auth-register');

Route::post('/login',[UserAuthController::class,'loginUser'])->name('auth-post-login');
Route::post('/register',[UserAuthController::class,'registerUser'])->name('auth-post-register');


Route::middleware('auth')->group(function (){
    Route::get('/',[ RoomController::class, 'room' ])->name('room');
    Route::get('/test',[ RoomController::class, 'test' ])->name('test');
    Route::get('/logout',[UserAuthController::class,'logoutUser'])->name('auth-logout');

});
