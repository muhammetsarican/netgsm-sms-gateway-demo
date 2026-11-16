<?php

use App\Http\Controllers\SmsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/sms', [SmsController::class, 'store'])->middleware('throttle:sms-send')->name('api.sms.store');

Route::get('/sms/{message}', [SmsController::class, 'show'])->name('api.sms.show');
