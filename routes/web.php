<?php

use App\Http\Controllers\DeployToolsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Server deploy tools (secured with DEPLOY_SECRET)
|--------------------------------------------------------------------------
*/
Route::middleware('deploy.secret')->get('/deploy-tools', [DeployToolsController::class, 'showTools']);

/*
|--------------------------------------------------------------------------
| SPA Catch-All - Serve Vue.js app for all non-API web routes
|--------------------------------------------------------------------------
*/
Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '.*');
