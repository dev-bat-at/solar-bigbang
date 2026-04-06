<?php

use App\Http\Controllers\Api\QuoteCalculatorController;
use Illuminate\Support\Facades\Route;

Route::post('/quote/calculate', QuoteCalculatorController::class)
    ->middleware('throttle:30,1')
    ->name('api.quote.calculate');
