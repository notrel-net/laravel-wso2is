<?php

use Illuminate\Support\Facades\Route;
use Donmbelembe\LaravelWso2is\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| WSO2IS Routes
|--------------------------------------------------------------------------
|
| Here are the routes that are loaded by the WSO2IS package.
|
*/

Route::group(['prefix' => 'wso2is', 'middleware' => ['web']], function () {
    Route::get('login', [AuthController::class, 'login'])->name('wso2is.login');
    Route::get('callback', [AuthController::class, 'callback'])->name('wso2is.callback');
    Route::post('logout', [AuthController::class, 'logout'])->name('wso2is.logout');
});
