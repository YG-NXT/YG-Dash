<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\MobileManifestController;
use App\Http\Controllers\Api\MobileSchemaController;

Route::middleware('api.json')->group(function () {

    Route::post('/login', [AuthApiController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', function (Request $request) {
            return $request->user();
        });
        Route::post('/logout', [AuthApiController::class, 'logout']);
        Route::post('/refresh', [AuthApiController::class, 'refresh']);
        Route::post('/change-password', [AuthApiController::class, 'changePassword']);
        Route::post('/edit-profile', [AuthApiController::class, 'editProfile']);
        Route::delete('/delete-account', [AuthApiController::class, 'deleteAccount']);

        // get staff user
        Route::get('/staff-user', [AuthApiController::class, 'getStaffUser']);
        // get client user
        Route::get('/client-user', [AuthApiController::class, 'getClientUser']);
        // get vendor user
        Route::get('/vendor-user', [AuthApiController::class, 'getVendorUser']);

        // Mobile app manifest
        Route::get('/mobile/manifest', [MobileManifestController::class, 'manifest']);
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/mobile/menu', [MobileSchemaController::class, 'menu']);
        Route::get('/mobile/screen/{slug}', [MobileSchemaController::class, 'screen']);
    });
});
