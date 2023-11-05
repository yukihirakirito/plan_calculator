<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PlanCalculatorController;
use App\Http\Controllers\RetestController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::post('/createRetestPlan', [PlanCalculatorController::class, 'createRetestPlan']);
Route::get('/getPlanCreatorInformation', [RetestController::class, 'getPlanCreatorInformation']);
Route::get('/searchUserLogin', [RetestController::class, 'searchUserLogin']);
Route::get('/getTempPlanData', [RetestController::class, 'getTempPlanData']);
Route::post('/storeTempPlanData', [RetestController::class, 'storeTempPlanData']);
Route::post('/postCreateRetestPlan', [RetestController::class, 'postCreateRetestPlan']);
