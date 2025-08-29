<?php

use Illuminate\Support\Facades\Route;
use GrishonMunene\MpesaStk\Http\Controllers\MpesaController;

Route::prefix('api/mpesa')->middleware('api')->group(function () {
    Route::post('/stk-push', [MpesaController::class, 'stkPush']);
    Route::post('/callback', [MpesaController::class, 'callback']);
    Route::get('/stk-query/{checkoutRequestId}', [MpesaController::class, 'stkQuery']);
    Route::get('/transaction-status/{checkoutRequestId}', [MpesaController::class, 'getTransactionStatus']);
});
