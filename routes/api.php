<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserTypeController;
use App\Http\Controllers\InstitutionController;
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('users')->group(function () {
    Route::get('/', [UserController::class, 'index']);
    Route::get('/create', [UserController::class, 'create']);
    Route::post('/', [UserController::class, 'store']);
    Route::get('/statistics', [UserController::class, 'statistics']);
    Route::get('/academic-programs', [UserController::class, 'getAcademicPrograms']);
    Route::post('/bulk-action', [UserController::class, 'bulkAction']);
    Route::get('/{id}', [UserController::class, 'show']);
    Route::get('/{id}/edit', [UserController::class, 'edit']);
    Route::put('/{id}', [UserController::class, 'update']);
    Route::patch('/{id}', [UserController::class, 'update']);
    Route::delete('/{id}', [UserController::class, 'destroy']);
    Route::patch('/{id}/toggle-status', [UserController::class, 'toggleStatus']);
});

Route::apiResource('user-types', UserTypeController::class);
Route::get('user-types-select', [UserTypeController::class, 'getAllForSelect']);

Route::apiResource('institutions', InstitutionController::class);
Route::get('institutions-select', [InstitutionController::class, 'getAllForSelect']);
Route::get('institutions/country/{country}', [InstitutionController::class, 'getByCountry']);
Route::get('institutions-stats', [InstitutionController::class, 'getStats']);
