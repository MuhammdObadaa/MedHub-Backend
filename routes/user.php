<?php

use App\Http\Controllers\AuthController as auth;
use App\Http\Controllers\UserController as user;
use App\Http\Controllers\MedicineController as medicine;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\StatisticsController;
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

//TODO: for Obadaa: follow the conventions

//---- Tokens_need routes
Route::group(['prefix' => '/user', 'as' => 'user.'], function () { // tested

    Route::get('', [user::class, 'show'])->name('show');
    //TODO : IDA: switch with favor/id
    Route::post('/favor/{medicine}', [user::class, 'favor'])->name('favor');

    Route::post('/unFavor/{medicine}', [user::class, 'unFavor'])->name('unFavor');
    //5- returns a json file with favorite medicines of the user
    Route::get('/favorites', [medicine::class, 'favorites'])->name('favorites');

    Route::post('/logout', [auth::class, 'logout'])->name('logout');
    //TODO: password auth
    Route::post('/update', [user::class, 'update'])->name('update');

    Route::get('/stat', [StatisticsController::class, 'userStat'])->name('stat');

    Route::get('/charts/{year}/{month}', [StatisticsController::class, 'userCharts'])->name('charts');

    Route::get('/report/{year1}/{month1}/{day1}/{year2}/{month2}/{day2}', [ReportsController::class, 'userReport'])->name('report');
    Route::get('/pdf/{year1}/{month1}/{day1}/{year2}/{month2}/{day2}', [ReportsController::class, 'pdfUserReport'])->name('pdf');
});
