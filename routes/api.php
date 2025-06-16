<?php

use App\Http\Controllers\CargasController;
use App\Http\Controllers\MonitoreoController;
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

Route::get('getInventarioEsferas', [CargasController::class, 'getInventarioEsferas'])->name('getInventarioEsferas'); # IRGE
Route::get('getTotalInvEsferas', [CargasController::class, 'getTotalInvEsferas'])->name('getTotalInvEsferas'); # IRGE
Route::get('getSellos', [CargasController::class, 'getSellos'])->name('getSellos'); # IRGE
Route::get('getCompanias', [CargasController::class, 'getCompanias'])->name('getCompanias'); # IRGE
Route::get('getCargasDiarias', [CargasController::class, 'getCargasDiarias'])->name('getCargasDiarias'); # IRGE
Route::get('getRDC', [CargasController::class, 'getRDC'])->name('getRDC'); # IRGE

Route::post('get_esferas', [MonitoreoController::class, 'get_esferas'])->name('get_esferas');
Route::post('get_llenaderas', [MonitoreoController::class, 'get_llenaderas'])->name('get_llenaderas');
Route::get('get_report_esferas', [MonitoreoController::class, 'get_report_esferas'])->name('get_report_esferas');
