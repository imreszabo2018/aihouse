<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PartnerController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'Invoice Processor API is running',
    ]);
});

Route::get('/partners', [PartnerController::class, 'index']);
Route::get('/partners/{partner}', [PartnerController::class, 'show']);
Route::post('/partners', [PartnerController::class, 'store']);
Route::put('/partners/{partner}', [PartnerController::class, 'update']);
Route::delete('/partners/{partner}', [PartnerController::class, 'destroy']);