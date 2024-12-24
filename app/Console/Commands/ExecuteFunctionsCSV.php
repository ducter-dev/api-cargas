<?php

namespace App\Console\Commands;

use App\Http\Controllers\CargasController;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExecuteFunctionsCSV extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:execute-functions-c-s-v';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ejecutar funciones de lunes a viernes a las 6 am';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $controller = new CargasController();
        $date = Carbon::now('America/Mexico_City')->format('Y-m-d H:i:s');

        Log::info("Comando iniciado: $date");

        try {

            Log::info('Iniciando función: getSellos');
            $controller->getSellos();
            Log::info('getSellos ejecutada con éxito.');

            Log::info('Iniciando función: getCompanias');
            $controller->getCompanias();
            Log::info('getCompanias ejecutada con éxito.');

            Log::info('Iniciando función: getCargasDiarias');
            $controller->getCargasDiarias();
            Log::info('getCargasDiarias ejecutada con éxito.');

            Log::info('Iniciando función: getRDC');
            $controller->getRDC();
            Log::info('getRDC ejecutada con éxito.');

            Log::info('Iniciando función: getInventarioEsferas');
            $controller->getInventarioEsferas();
            Log::info('getInventarioEsferas ejecutada con éxito.');

            Log::info('Iniciando función: getTotalInvEsferas');
            $controller->getTotalInvEsferas();
            Log::info('getTotalInvEsferas ejecutada con éxito.');

        } catch (\Exception $e) {
            Log::error('Error al ejecutar una función: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            $this->error('Error al ejecutar el comando. Revisa los logs.');
        }

        Log::info("Comando finalizado: $date");
    }
}
