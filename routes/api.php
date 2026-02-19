<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\PartyController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('auth/users/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('auth/users/me', [AuthController::class, 'me']);
        Route::post('auth/users/logout', [AuthController::class, 'logout']);

        Route::get('users', [UserController::class, 'index']);
        Route::get('users/{id}', [UserController::class, 'show']);

        Route::get('parties', [PartyController::class, 'index']);
        Route::get('parties/{id}', [PartyController::class, 'show']);

        Route::middleware('can.manage.users')->group(function () {
            Route::post('users', [UserController::class, 'store']);
            Route::match(['put', 'patch'], 'users/{id}', [UserController::class, 'update']);
            Route::delete('users/{id}', [UserController::class, 'destroy']);

            Route::post('parties', [PartyController::class, 'store']);
            Route::match(['put', 'patch'], 'parties/{id}', [PartyController::class, 'update']);
            Route::delete('parties/{id}', [PartyController::class, 'destroy']);
        });
    });
});
