<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class Actualizarllenaderas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:actualizarllenaderas';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando actualización de llenaderas...');

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

       $response = Http::put('https://api-mon.ducter-management.com/api/v1/loading-realtime/update-all', [
            'data' => $datosFormateados
        ]);

        if ($response->successful()) {
            $this->info('Llenaderas actualizadas correctamente.');
        } else {
            $this->error('Error al actualizar llenaderas: ' . $response->body());
        }

        return Command::SUCCESS;
    }
}
