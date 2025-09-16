<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CountryController;
use App\Http\Controllers\Api\PlayerController;
use App\Http\Controllers\Api\TransferController;
use App\Http\Controllers\Api\TransferListingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/countries', [CountryController::class, 'index']);

Route::get('/transfer-listings', [TransferListingController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/players', [PlayerController::class, 'index']);
    Route::post('/transfer-listings', [TransferListingController::class, 'store']);
    Route::post('/transfer/purchase/{player}', [TransferController::class, 'purchasePlayer']);
    Route::put('/players/{player}', [PlayerController::class, 'update']);
});
