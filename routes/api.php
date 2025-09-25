<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UnityController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Rotas públicas
Route::post('/register', [AuthController::class, 'register']);

Route::post('/login', [AuthController::class, 'login']);


Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('admin')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard']);

        Route::apiResource('/companies', CompanyController::class);
        Route::apiResource('/products', ProductController::class);
        // Route::apiResource('/categories', ProductCategoryController::class);
    });

    Route::apiResource('/unities', UnityController::class);
    Route::apiResource('/categories', ProductCategoryController::class);
});


// Rotas de verificação de email
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->middleware(['signed'])
    ->name('api.verification.verify');

Route::get('/email/verify', [AuthController::class, 'sendVerificationNotice'])
    ->middleware(['auth:sanctum'])
    ->name('verification.notice');

Route::post('/email/resend', [AuthController::class, 'resendVerificationEmail'])
    ->middleware(['auth:sanctum', 'throttle:6,1'])
    ->name('verification.resend');

// Rotas autenticadas
Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::get('/user', function (Request $request) {
        return response()->json([
            'user' => $request->user(),
            'email_verified' => $request->user()->hasVerifiedEmail()
        ]);
    });

    Route::post('/logout', [AuthController::class, 'logout']);
});

// Rotas de exemplo para usuários (protegidas)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::apiResource('users', UserController::class);
});
