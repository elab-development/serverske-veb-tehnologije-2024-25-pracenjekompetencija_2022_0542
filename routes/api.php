<?php

use App\Http\Controllers\VerificationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CredentialController;
use App\Http\Controllers\RorController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the 'api' middleware group. Enjoy building your API!
|
*/

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('categories', [CategoryController::class, 'index']);
Route::get('categories/{category}', [CategoryController::class, 'show']);
Route::get('categories/{category}/credentials', [CategoryController::class, 'credentials']);

Route::get('credentials', [CredentialController::class, 'index']);
Route::get('credentials/{credential}', [CredentialController::class, 'show']);

Route::get('verification/{status}/credentials', [VerificationController::class, 'credentialsByStatus']);

Route::get('issuers/ror/verify', [RorController::class, 'verify']);

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::resource('categories', CategoryController::class)
        ->only(['store', 'update', 'destroy']);

    Route::resource('credentials', CredentialController::class)
        ->only(['store', 'update', 'destroy']);

    Route::post('verifications', [VerificationController::class, 'store']);
});
