<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SoapWalletController;

Route::post('/soap', [SoapWalletController::class, 'handle']);
Route::get('/wsdl', function () {
    return response()->file(public_path('service.wsdl'));
});