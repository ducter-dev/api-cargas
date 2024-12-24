<?php

namespace App\Console;

use App\Console\Commands\ExecuteFunctionsCSV;
use App\Http\Controllers\CargasController;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
          $schedule->call(function(){
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

                }

                Log::info("Comando finalizado: $date");
            })
            ->dailyAt('06:01')
            ->timezone('America/Mexico_City')
            ->appendOutputTo(storage_path('logs/scheduler.log'));


    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
