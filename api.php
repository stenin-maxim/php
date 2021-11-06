<?php

use Illuminate\Support\Facades\Route;

Route::group(['middleware' => 'auth:sanctum'], function () {   
	Route::prefix('recycler')->group(function () {
        Route::prefix('production-spots')->group(function () {
            Route::get('/', [\App\Http\Controllers\Recycler\RecyclerProductionSpotController::class, 'getProductionSpots']);
            Route::get('{production_spot}/general-info', [\App\Http\Controllers\Recycler\RecyclerProductionSpotController::class, 'getGeneralInfo']);

            Route::post('main-info', [\App\Http\Controllers\Recycler\RecyclerProductionSpotController::class, 'createMainInfo']);
            Route::put('{production_spot}/main-info', [\App\Http\Controllers\Recycler\RecyclerProductionSpotController::class, 'updateMainInfo']);

            Route::get('photos/{photo}', [\App\Http\Controllers\Recycler\RecyclerProductionSpotController::class, 'getResizedPhoto']);
            Route::post('photos', [\App\Http\Controllers\Recycler\RecyclerProductionSpotController::class, 'uploadProductionSpotPhotos']);
            Route::delete('photos/{photo}', [\App\Http\Controllers\Recycler\RecyclerProductionSpotController::class, 'deletePhoto']);
        }
   	}
}
