<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Throwable;

class MonitoreoController extends Controller
{
    public function get_report_esferas(Request $request)
    {
        try {
            $fecha = Carbon::now('America/Mexico_City')->format('d-m-Y');
            $tiempo = Carbon::now('America/Mexico_City')->format('H:i');
            $esferas = [1, 2];
            $datos = [];
            $fallbackEntry='';

            foreach ($esferas as $tanqueEsferico) {

                $response = Http::get("https://api-mon.ducter-management.com/api/v1/sphere-report/last-date/$tanqueEsferico");

                $lastEntryFromAPI = $response->successful() ? $response->json('data.entry_id') : null;

                if ($lastEntryFromAPI) {
                    $resultado = DB::table('ValEsferas')
                        ->where('EntryID', '>', $lastEntryFromAPI)
                        ->where('Esfera', $tanqueEsferico)
                        ->get();
                    } else {
                        $fallbackEntry = Carbon::now('America/Mexico_City')->format('YmdH');
                        $fechaConvertida = Carbon::createFromFormat('d-m-Y', $fecha)->format('d/m/Y');

                    $resultado = DB::table('ValEsferas')
                        ->where('EntryID', '<', $fallbackEntry)
                        ->where('Esfera', $tanqueEsferico)
                        ->where('Fecha', $fechaConvertida)
                        ->get();
                }

                if ($resultado->isNotEmpty()) {
                    foreach ($resultado as $fila) {
                        $datos[] = (array) $fila;
                    }
                }
            }

            foreach($datos as $item){
                $dataFormateada= [
                    'sphere_id' => $item['Esfera'],
                    'entry_id' => $item['EntryID'],
                    'date' => Carbon::createFromFormat('d/m/Y', $item['Fecha'])->format('Y-m-d'),
                    'time' => $item['Tiempo'],
                    'date_time' => Carbon::createFromFormat('n/j/Y g:i:s A', $item['TiempoMaquina'])->format('Y-m-d H:i:s'),
                    'report_date' => Carbon::createFromFormat('Ymd', $item['DiaReporte24'])->format('Y-m-d'),
                    'density' => $item['DensidadNat'],
                    'density20' => $item['DensidadCor'],
                    'temperature' => $item['Temp'],
                    'lower_pressure' => $item['Presion'],
                    'dome_pressure' => $item['Presion'],
                    'mass' => $item['VolTon'],
                    'nat_mass' => $item['BLSNat'],
                    'volume' => $item['VolTon'],
                    'barrel_volume' => $item['BLSNat'],
                    'corrected_volume' => $item['BLSCor'],
                    'corr_barrel_volume' => $item['BLSCor'],
                    'level' => $item['Nivel'],
                    'percent_level' => $item['Porcentaje'],
                ];

                $response = Http::withHeaders([
                    'Accept' => 'application/json',
                ])->post('https://api-mon.ducter-management.com/api/v1/sphere-report', $dataFormateada);

               if (!$response->successful()) {
                dump([
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'json' => $response->json(),
                    'data_enviada' => $dataFormateada,
                ]);
            }
            }

            return response()->json([
                "message" => 'consultado correctamente.',
                "status" => 200,
                "fecha" => $fecha,
                "tiempo" => $fallbackEntry ? $fallbackEntry : $lastEntryFromAPI ,
                "data" => $datos,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Error al generar el archivo CSV.',
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function get_esferas(Request $request){
    try{

        $data = DB::connection('mysql')->table('esferas_web')->get()->toArray();

        $datosFormateados = array_map(function ($item) {
            return [
                'density'            => $item->densidad,
                'density20'          => $item->densidadCorregida,
                'temperature'        => $item->temperaturaEsfera,
                'lower_pressure'     => $item->presionFondo,
                'dome_pressure'      => $item->presionDomo,
                'mass'               => $item->masa,
                'volume'             => $item->volumen,
                'barrel_volume'      => $item->volumenBarril,
                'corrected_volume'   => $item->volumenCorregido,
                'corr_barrel_volume' => $item->volumenCorrBarril,
                'level'              => $item->nivel,
                'percent_level'      => $item->nivelPorcentaje,
                'id'                 => $item->id == 'TE301A' ? 'TE-101A' : 'TE-101B',
            ];
        }, $data);

      Http::put('https://api-mon.ducter-management.com/api/v1/sphere-realtime/update-all', [
            'data' => $datosFormateados
        ]);

    return response()->json([
    "message" => 'consultado correctamente.',
    "status" => 200,
    "data" => $datosFormateados
    ], 200);

    }catch(Throwable $th){
    return response()->json([
    'message' => 'Error.',
    'error' => $th->getMessage(),
    ], 500);
    }
    }

    public function get_llenaderas(Request $request){
    try{

    $data = DB::connection('mysql')->table('llenaderas_web')->get()->toArray();

    $datosFormateados = array_map(function ($item) {
        return [
            'status'         => $item->estado,
            'pg'             => $item->pg,
            'partial'        => $item->parcial,
            'percent'        => $item->porcentaje,
            'scheduled'      => $item->programado,
            'flow_rate'      => $item->flujo,
            'loading_acces'  => $item->entradaCarga,
            'loading_start'  => $item->inicioCarga,
            'loading_finish' => $item->finCarga,
            'id'             => $item->id,
        ];
    }, $data);

    Http::put('https://api-mon.ducter-management.com/api/v1/loading-realtime/update-all', [
        'data' => $datosFormateados
    ]);

    return response()->json([
    "message" => 'consultado correctamente.',
    "status" => 200,
    "data" => $datosFormateados,
    ], 200);

    }catch(Throwable $th){
    return response()->json([
    'message' => 'Error.',
    'error' => $th->getMessage(),
    ], 500);
    }
    }



}
