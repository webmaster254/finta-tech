<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MpesaController;
use App\Http\Controllers\DtbController;
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

Route::get('/', function () {
    return view('welcome');
});


Route::get('registerurl', 'App\Http\Controllers\MpesaController@registerUrl');
Route::get('confirmationpull', 'App\Http\Controllers\MpesaController@ConfirmationPull');
Route::get('simulate', 'App\Http\Controllers\MpesaController@Simulate');
Route::post('validation', 'App\Http\Controllers\MpesaController@Validation');
Route::post('confirmation', 'App\Http\Controllers\MpesaController@Confirmation');
Route::post('registerPullApi', 'App\Http\Controllers\MpesaController@registerPullApiUrl');
Route::post('pullTransactions', 'App\Http\Controllers\MpesaController@pullTransactions');
Route::post('pull', 'App\Http\Controllers\MpesaController@sendMpesaPullTransactionRequest');
Route::get('saftoken', 'App\Http\Controllers\MpesaController@generateAccessToken');
// Initiate M-PESA STK Request
Route::get('stkinitiate', [MpesaController::class, 'initiateStkRequest']);

// Handle the M-PESA STK Callback
Route::post('stkcallback', [MpesaController::class, 'handleStkCallback']);

// Fetch M-PESA STK Payments
Route::get('stkpayments', [MpesaController::class, 'fetchStkPayments']);

Route::get('dtb/confirmation', [DtbController::class, 'Confirmation']);

Route::get('b2c', [MpesaController::class, 'b2c']);
Route::post('b2cresult', [MpesaController::class, 'b2cResult']);
Route::post('b2ctimeout', [MpesaController::class, 'b2cTimeout']);

Route::post('/dtb-token',[DtbController::class, 'generateToken']);
Route::post('/dtb/b2c', [DtbController::class, 'DtbB2C']);
Route::post('dtb/balance', [DtbController::class, 'bankBalance']);
Route::post('dtb/validate-id', [DtbController::class, 'validateID']);
Route::get('dtb/confirmation', [DtbController::class, 'Confirmation']);
Route::post('dtb/payment-notification', [DtbController::class, 'sendPaymentNotification']);
