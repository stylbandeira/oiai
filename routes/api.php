<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\FavoriteProductsController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ListController;
use App\Http\Controllers\ListItensController;
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
        Route::post('/users/revertDeleted/{id}', [UserController::class, 'revertDestroy']);
        Route::get('/users/export', [UserController::class, 'export']);
        Route::get('/dashboard', [AdminController::class, 'dashboard']);

        Route::apiResource('/companies', CompanyController::class);
        Route::post('/products/import', [ProductController::class, 'import']);
        Route::get('/products/export', [ProductController::class, 'export']);
        Route::post('/products/bulk-validate', [ProductController::class, 'bulkValidate']);
        Route::apiResource('/products', ProductController::class);
        Route::apiResource('/users', UserController::class);
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

Route::get('/lists/{id}', [ListController::class, 'show']);
Route::put('/listItems/{id}', [ListItensController::class, 'update']);

// Rotas autenticadas
Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::get('/user', [AuthController::class, 'user']);

    // LIST
    Route::post('/lists/{list}/optimize', [ListController::class, 'optimize']);
    // Route::apiResource('/lists', ListController::class);

    Route::get('/lists', [ListController::class, 'index']);
    Route::post('/lists', [ListController::class, 'store']);
    Route::put('/lists/{id}', [ListController::class, 'update']);
    Route::delete('/lists/{id}', [ListController::class, 'destroy']);

    // Route::apiResource('/listItems', ListItensController::class);

    // PRODUCTS
    Route::get('/products', [ProductController::class, 'index']);
    //FAVORITE-PRODUCTS
    Route::post('/products/{product}/favorite', [FavoriteProductsController::class, 'favorite']);

    // INVOICES
    Route::post('/invoice/process', [InvoiceController::class, 'processInvoice']);
    Route::post('/invoice/processXML', [InvoiceController::class, 'processXML']);

    //EVENTS
    Route::apiResource('/events', EventController::class);
    Route::post('/events/check-all', [EventController::class, 'checkAll']);

    // COMPANIES
    Route::get('/companies/dashboard-data', [CompanyController::class, 'dashboardData']);
    Route::post('/companies/submit', [CompanyController::class, 'submit']);
    Route::apiResource('/companies', CompanyController::class);

    Route::get('/dashboard-data', [UserController::class, 'dashboardData']);

    Route::post('/logout', [AuthController::class, 'logout']);
});
