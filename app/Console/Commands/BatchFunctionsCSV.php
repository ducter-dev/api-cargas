<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BatchFunctionsCSV extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:batch-functions-c-s-v {start_date} {end_date}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ejecutar funciones en un rango de fechas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startDate = Carbon::parse($this->argument('start_date'));
        $endDate = Carbon::parse($this->argument('end_date'));

        if ($startDate->greaterThan($endDate)) {
            $this->error('La fecha de inicio no puede ser mayor que la fecha de fin.');
            return Command::FAILURE;
        }

        Log::info("Comando iniciado desde {$startDate->toDateString()} hasta {$endDate->toDateString()}");

        $allFilenames = [];

        // Array asociativo con las funciones y sus rutas nombradas.
        $functions = [
            'getInventarioEsferas' => 'getInventarioEsferas',
            'getTotalInvEsferas' => 'getTotalInvEsferas',
            'getSellos' => 'getSellos',
            'getCompanias' => 'getCompanias',
            'getCargasDiarias' => 'getCargasDiarias',
            'getRDC' => 'getRDC',
        ];

        try {
            for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
                $jsonPayload = ['fecha' => $date->toDateString()];
                Log::info("Ejecutando funciones para la fecha: {$date->toDateString()}");

                foreach ($functions as $functionName => $routeName) {
                    Log::info("Iniciando función: $functionName");

                    $response = Http::withOptions(['verify' => false])
                        ->get(route($routeName), $jsonPayload);

                    if ($response->successful()) {
                        $data = $response->json();

                        Log::info("Mensaje de $functionName: " . ($data['message'] ?? 'Sin mensaje'));
                        Log::info("Status de $functionName: " . ($data['status'] ?? 'Sin status'));
                        Log::info("Archivos de $functionName: " . print_r($data['files'] ?? [], true));

                        if (isset($data['files']) && is_array($data['files'])) {
                            $allFilenames = array_merge($allFilenames, $data['files']);
                        } else {
                            Log::warning("La respuesta de $functionName no contiene un array de 'files' válido o está vacío.");
                        }

                        Log::info("$functionName ejecutada con éxito para la fecha {$date->toDateString()}.");
                    } else {
                        Log::error("Error al obtener datos de $functionName: " . $response->status() . ' - ' . $response->body());
                        $this->error("Error al ejecutar $functionName. Revisa los logs.");
                        return Command::FAILURE;
                    }
                }
            }

            // Enviar correo si hay archivos generados
            Log::info("Total de archivos generados: " . count($allFilenames));
            Log::info("Archivos generados: " . print_r($allFilenames, true));
        } catch (\Exception $e) {
            Log::error('Error general: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            $this->error('Error al ejecutar el comando. Revisa los logs.');
            return Command::FAILURE;
        }

        Log::info("Comando finalizado");
        return Command::SUCCESS;
    }
}
