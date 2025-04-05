<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DtbController;
use App\Http\Controllers\v1\LoansController;
use App\Http\Controllers\v1\MediaController;
use App\Http\Controllers\v1\MpesaController;
use App\Http\Controllers\v1\UsersController;
use App\Http\Controllers\v1\ClientsController;
use App\Http\Controllers\v1\DashboardController;


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

Route::middleware('auth:sanctum')->post('/user', function (Request $request) {
    return $request->user();
});




Route::prefix('/v1')->group(function () {
Route::post('/users', [UsersController::class,'login']);
Route::get('/dashboard', [DashboardController::class,'index']);
Route::get('/monthly-par', [DashboardController::class,'monthlyPar']);
Route::get('/disbursement', [DashboardController::class,'totalDisbursementPerMonth']);
Route::get('/daily-repayment-schedule', [LoansController::class,'dailyRepaymentSchedule']);
Route::get('/clients-arrears', [DashboardController::class,'clientsWithArrears']);
Route::get('/mpesa-payments', [DashboardController::class,'mpesaPayments']);
Route::get('/loans', [LoansController::class,'index']);
Route::get('/loans/{id}', [LoansController::class,'show']);
Route::get('/loans/repayments/{id}', [LoansController::class,'repaymentSchedules']);
Route::get('/loans/transactions/{id}', [LoansController::class,'transactions']);
Route::get('/loans/guarantors/{id}', [LoansController::class,'guarantors']);
Route::get('/search-loans', [LoansController::class,'searchLoans']);
Route::get('/clients', [ClientsController::class,'index']);
Route::get('/mpesa', [MpesaController::class,'index']);
Route::get('/clients/{id}', [ClientsController::class,'show']);
Route::get('/clients/loan/{id}', [ClientsController::class,'loans']);
Route::get('/search-clients', [ClientsController::class,'searchClients']);
Route::get('/media', [MediaController::class, 'index']);
Route::post('/dtb-token',[DtbController::class, 'generateToken']);
Route::post('/dtb/b2c', [DtbController::class, 'DtbB2C']);
Route::post('dtb/balance', [DtbController::class, 'bankBalance']);
Route::post('dtb/validate-id', [DtbController::class, 'validateID']);
Route::post('dtb/confirmation', [DtbController::class, 'Confirmation']);
Route::post('dtb/payment-notification', [DtbController::class, 'sendPaymentNotification']);
  


// Login Route
Route::post('/login', function (Request $request) {
    if (! Auth::attempt($request->only('email', 'password'))) {
        return response()->json(['error' => __('auth.failed')], 401); // Changed to return JSON error response with status 401
    }

    $user = auth()->user(); // Get the authenticated user
    $token = $user->createToken('client-app');
    return [
        'token' => $token->plainTextToken,
        'user' => $user // Include user data in the response
    ];
});
// Logout Route
Route::middleware('auth:sanctum')->post('/logout', function (Request $request) {
    $request->user()->currentAccessToken()->delete();
    return response()->noContent();
});
});
