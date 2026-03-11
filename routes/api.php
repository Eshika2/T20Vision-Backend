<?php

//use Illuminate\Http\Request;

use App\Http\Controllers\SettingController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

Route::middleware('check.app.version')->group(function () {
    Route::post('auth/register', [UserController::class, 'userRegister']);
    Route::post('auth/login', [UserController::class, 'userLogin']);
    Route::post('auth/validate', [UserController::class, 'userValidate']);
    Route::post('user/all', [UserController::class, 'userAll']);
    Route::post('user/password/reset', [UserController::class, 'userPasswordReset']);

    Route::post('auth/otp/generate', [UserController::class, 'generateOTP']);
    Route::post('auth/otp/verify', [UserController::class, 'verifyOTP']);

    Route::group(['middleware' => ['jwt.verify']], function () {
        Route::post('auth/user', [UserController::class, 'userData']);
        Route::post('/logout', [UserController::class, 'logout']);

        Route::post('prediction/win', [SettingController::class, 'winPrediction']);
        Route::post('prediction/score', [SettingController::class, 'scorePrediction']);

    });
});