<?php

use App\Http\Controllers\Api\ServerTimeController;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/server-time', [ServerTimeController::class, 'show'])->name('api.server-time');

Route::get('/user', function (Request $request) {
    return ApiResponse::success($request->user());
})->middleware('auth:sanctum')->name('api.user');
