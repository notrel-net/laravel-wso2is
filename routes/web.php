<?php

use Illuminate\Support\Facades\Route;
use Laravel\Wso2is\Http\Controllers\CallbackController;

/*
|--------------------------------------------------------------------------
| WSO2IS Routes
|--------------------------------------------------------------------------
|
| Here are the routes that are loaded by the WSO2IS package.
|
*/

Route::group(['prefix' => 'wso2is', 'middleware' => ['web']], function () {
    Route::get('callback', [CallbackController::class, 'handle'])->name('wso2is.callback');
});
