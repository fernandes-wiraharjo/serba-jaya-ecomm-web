<?php

use App\Http\Controllers\Api\ReferralCodeController;
use App\Http\Controllers\Api\RegisterController;
use App\Http\Controllers\Api\ServerTimeController;
use App\Http\Controllers\Api\SocialAuthController;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/server-time', [ServerTimeController::class, 'show'])->name('api.server-time');

Route::get('/auth/google/url', [SocialAuthController::class, 'googleUrl'])->name('api.auth.google.url');
Route::post('/auth/google', [SocialAuthController::class, 'google'])->name('api.auth.google');
Route::post('/auth/register', [RegisterController::class, 'store'])
    ->middleware('auth:sanctum')
    ->name('api.auth.register');
// Apple sign-in is disabled for now.
// Route::post('/auth/apple', [SocialAuthController::class, 'apple'])->name('api.auth.apple');

Route::get('/user', function (Request $request) {
    return ApiResponse::success($request->user());
})->middleware('auth:sanctum')->name('api.user');

Route::post('/user/referral/code-check', [ReferralCodeController::class, 'check'])
    ->middleware('auth:sanctum')
    ->name('api.user.referral.code-check');
