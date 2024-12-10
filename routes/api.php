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
Route::post('getCargasIRGE_Periodo', [CargasController::class, 'getCargasIRGE_Periodo']);
Route::get('getInventarioEsferas', [CargasController::class, 'getInventarioEsferas']); # IRGE
Route::get('getTotalInvEsferas', [CargasController::class, 'getTotalInvEsferas']); # IRGE
Route::get('getSellos', [CargasController::class, 'getSellos']); # IRGE
Route::get('getCompanias', [CargasController::class, 'getCompanias']); # IRGE
Route::get('getCargasDiarias', [CargasController::class, 'getCargasDiarias']); # IRGE
Route::get('getRDC', [CargasController::class, 'getRDC']); # IRGE
