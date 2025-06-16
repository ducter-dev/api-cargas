<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class InventarioEsferas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:inventario-esferas';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Obtiene informacion sobre el inventario de las esferas por hora';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Cargando inventario de esferas.');
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

            $this->info('Inventario de esfera cargado correctamente.');
            return Command::SUCCESS;

        } catch (\Throwable $th) {
            $this->error('Error al cargar los inventarios: ' . $response->body());
            return Command::FAILURE;
        }
    }
}
