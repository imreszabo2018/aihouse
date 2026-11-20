<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PartnerController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\InvoiceUploadController;

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
Route::get('/invoices', [InvoiceController::class, 'index']);
Route::get('/invoices/{invoice}', [InvoiceController::class, 'show']);
Route::delete('/invoices/{invoice}', [InvoiceController::class, 'destroy']);
Route::post('/invoices', [InvoiceUploadController::class, 'store']);