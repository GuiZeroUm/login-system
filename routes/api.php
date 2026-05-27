<?php

use App\Http\Controllers\Api\V1\LoginController;
use App\Http\Controllers\Api\V1\AclController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/login/{slug}', LoginController::class);
    Route::get('/check/{aclToken}', [AclController::class, 'check']);
    Route::get('/orgaos/{slug}/{orgaoId?}', [AclController::class, 'orgaos'])->whereNumber('orgaoId');
    Route::get('/lotacoes/{slug}/{orgaoId?}', [AclController::class, 'lotacoes'])->whereNumber('orgaoId');
    Route::get('/unidades/{slug}/{orgaoId?}', [AclController::class, 'lotacoes'])->whereNumber('orgaoId');
});

Route::post('/sistema', [AclController::class, 'sistemas'])->middleware('apiauth');

