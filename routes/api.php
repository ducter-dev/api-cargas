<?php

use App\Http\Controllers\CargasController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::post('getCargasIRGE', [CargasController::class, 'getCargasIRGE']);
Route::post('getFaltantesIRGE', [CargasController::class, 'getFaltantesIRGE']);
Route::post('getCargasTPA', [CargasController::class, 'getCargasTPA']);
Route::post('getFaltantesTPA', [CargasController::class, 'getFaltantesTPA']);
