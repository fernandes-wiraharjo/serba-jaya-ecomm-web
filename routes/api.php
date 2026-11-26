<?php

use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ReferralCodeController;
use App\Http\Controllers\Api\ServerTimeController;
use App\Http\Controllers\Api\SocialAuthController;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/server-time', [ServerTimeController::class, 'show'])->name('api.server-time');

Route::get('/auth/google/url', [SocialAuthController::class, 'googleUrl'])->name('api.auth.google.url');
Route::post('/auth/google', [SocialAuthController::class, 'google'])->name('api.auth.google');
// Apple sign-in is disabled for now.
// Route::post('/auth/apple', [SocialAuthController::class, 'apple'])->name('api.auth.apple');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/user', function (Request $request) {
        return ApiResponse::success($request->user());
    })->name('api.user');

    Route::post('/user/profile/update', [ProfileController::class, 'update'])
        ->name('api.user.profile.update');

    Route::post('/user/referral/code-check', [ReferralCodeController::class, 'check'])
        ->name('api.user.referral.code-check');
});
